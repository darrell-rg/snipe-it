# Here are some libraries that we'll need for this process
import pandas as pd
from datetime import datetime
# import pyodbc as dbapi
# from tqdm import tqdm

import requests
import os.path
from datetime import timedelta
import json
from collections import defaultdict
import re
import csv
import argparse
import math
import pprint
pp = pprint.PrettyPrinter(indent=4)
import snipeit
# from snipeit import Models

#!/usr/bin/python 
import mariadb 

server='https://dev2.sawpit.app'
token=''



def connect(pw):
    conn = mariadb.connect(
        user="snipeit",
        password="pw",
        host="localhost",
        database="snipeit")
    cur = conn.cursor() 
    return (conn, cur)


def getExistingModelsFromApi(server,token):
    #load a dict that maps name to model ids
    M = snipeit.Models()
    modelsDict = json.loads( M.get(server, token, 5000) )# Using default limit of 5000 for results
    existing_model_ids = defaultdict() #default of None
    for row in modelsDict['rows']:
        # csvSummary=(row)
        if row['name'] == 'DF-FOHC-8x8x24':
            pp.pprint(row)
        existing_model_ids[row['name']]=row['id']
    return existing_model_ids

def generateAllPossibleModels():

    # generate a list of beam sizes
    #Standard-- Standard lengths of lumber
    #shall be in multiples of 0.3048 m (1 foot) or 0.6096
    #m (2 feet) as specified in the certified grading 

    species_group = ["CEDAR","DOUGLAS FIR","PINE"]
    thickness = [5,6,7,8,9,10,11,12,14,16]
    width = [5,6,7,8,9,10,11,12,13,14,15,16,18,20]
    length = [4,5,6,8,10,12,14,16,18,20,22,24,28,32]
    pith = ["FOHC","HC","BHC"]


    unique_summaries=[]
    for sg in species_group:
        for t in thickness:
            for w in width:
                if w > t:
                    for l in length:
                        for p in pith:
                            summary = f"{sg} - {p} - {t}x{w}x{l}"
                            unique_summaries.append(summary) 
        


    print("number of model permutations:"+ str(len(unique_summaries)))


def get_nominal_model_summary(species,pith,w,h,l):
    # get the PS20 standard lumber size from actual size
    # the model should represent what a supplier would call this line item 
    thickness = math.floor(w) 
    width = math.floor(h) 
    length = math.floor(l)
    summary = f"{species} - {pith} - {thickness}x{width}x{length}"
    return summary


def summaryToModel(summ,useHC=True,useDims=True,useLength=True,useNominal=True):

   
    # s looks ike   "POC - FOHC - 6x6x10"
    #remove whitespace
    s = summ.split('-')
    species = s[0].strip()
    heartCenter = ''
    if len(s)>1:
        heartCenter = s[1].strip()
    dims = ''
    w=None
    h=None
    l=None
    bdf=0
    if len(s) > 2:
        dims = s[2].strip()

    if len(dims) > 2:
        #print(summ)
        dims = dims.split('x')
        w=float(dims[0])
        h=float(dims[1])
        
        if useNominal and w:
            w = math.floor(w)
        if useNominal and h:
            h = math.floor(h)

        if useLength and len(dims) > 2:
            l=float(dims[2])
            if useNominal and l:
                l = math.floor(l)
            dims = 'x'.join([str(w),str(h),str(l)])
        else:
            dims = 'x'.join([str(w),str(h)])
            l=None

    if(w and h and l):
        bdf = math.floor(((w*h)/12)*l)

    # category 2 is heavy timber
    manufacturer_id = 1 # 1 is Unknown
    if species ==  "DF":
        manufacturer_id = 2
    if species ==  "POC":
        manufacturer_id = 3
    if species ==  "AYC":
        manufacturer_id = 4
    if species ==  "WRC":
        manufacturer_id = 5
    if species ==  "WO":
        manufacturer_id = 6

    # model_number can come from the supplier Invoice if neeeded
    model_number = ""
    
    summary = '-'.join([species])
    if useHC:
        summary = '-'.join([species,heartCenter])
    if useDims:
        summary = '-'.join([species,heartCenter,dims])
    # model dims are nominal,  asset dims are actual
    # we need to use length for model to rapidly enter a truck via a kit
    # for the same reason it may be faster to have FOHC in the model_no
    # 
    #can you please remove all the fields from this json array, except for db_column_name and default_value?

    defaults  = [   
        {   'db_column_name': '_snipeit_species_22',
            'default_value': species
        },
        {   'db_column_name': '_snipeit_pith_18',
            'default_value': heartCenter
        },
        {   'db_column_name': '_snipeit_thickness_4',
            'default_value': w
        },
        {   'db_column_name': '_snipeit_width_5',
            'default_value': h
        },
        {   'db_column_name': '_snipeit_length_7',
            'default_value': l
        },
        {   'db_column_name': '_snipeit_bdf_8',
            'default_value': bdf
        },
        {   'db_column_name': '_snipeit_pith_18',
            'default_value': heartCenter
        },
        {   'db_column_name': '_snipeit_grade_2',
            'default_value': "#1"
        },
        {   'db_column_name': '_snipeit_condition_9',
            'default_value': None
        },
        {   'db_column_name': '_snipeit_moisture_3',
            'default_value': 'S-GRN'
        },
        {   'db_column_name': '_snipeit_bdf_cost_10',
            'default_value': None
        },
        {   'db_column_name': '_snipeit_freight_11',
            'default_value': None
        },
        {   'db_column_name': '_snipeit_markup_12',
            'default_value': 1.25
        },
        {   'db_column_name': '_snipeit_units_23',
            'default_value': 'Nominal in/ft'
        }
    ]

    d = {"name":summary, "category_id":2, "manufacturer_id":manufacturer_id, "fieldset_id":2, "model_number":model_number, "default_fieldset_values":defaults}

    # assignCustomFieldsDefaultValues
    return d


def summaryToModelNoDims(s):
    return summaryToModel(s,useHC=True,useDims=False,useLength=False)

def summaryToModelNoLength(s):
    return summaryToModel(s,useHC=True,useDims=True,useLength=False)

def summaryToModelSpeciesOnly(s):
    return summaryToModel(s,useHC=False,useDims=False,useLength=False)

def modelToSummary(m,useHC=True):
    if useHC:
        return m['name']
    species = m['name'].split('-')[0].strip()
    dims = (m['name'].split('-')[1] or '').strip()
    return ' - '.join(species,m['model_number'],dims).strip()

def getModelsFromDB(cur):
    existing_model_ids = {}
    cur.execute("SELECT id, name FROM models WHERE fieldset_id = ?", (2,)) 

    for id, name  in cur: 
        print(f"id: {id}, name: {name}")
        existing_model_ids[id] = summaryToModel(name)
    
    return existing_model_ids


def getExistingDefaultID(cur,asset_model_id, custom_field_id):

    cur.execute("SELECT id, asset_model_id FROM models_custom_fields WHERE asset_model_id =? AND custom_field_id = ?", (asset_model_id, custom_field_id)) 
    for id,asset_model_id  in cur: 
        return id
    return None


def installModelCustomFields(cur,conn,models):

    for asset_model_id, m in models.items():     
        print(f"adding custom defaults for {m['name']}")
        for d in m['default_fieldset_values']:
            custom_field_id = d['db_column_name'].split('_')[-1]
            default_value = d['default_value']
            if default_value:
                #insert information 
                existing_id = getExistingDefaultID(cur,asset_model_id, custom_field_id)
                    
                if existing_id:
                    sql =  f'''INSERT INTO models_custom_fields (id, asset_model_id, custom_field_id, default_value)
                            VALUES ( {existing_id},{asset_model_id},{custom_field_id},'{default_value}')
                            ON DUPLICATE KEY UPDATE''' 

                else:
                    sql =  f'''INSERT INTO models_custom_fields (asset_model_id, custom_field_id, default_value)
                            VALUES ({asset_model_id}, {custom_field_id}, '{default_value}')'''
                            
                try: 
                    cur.execute(sql) 
                except mariadb.Error as e: 
                    print (sql)
                    print(f"Error: {e}")

    conn.commit() 
    print(f"Last Inserted ID: {cur.lastrowid}")
    conn.close()




def updateModel(modelID, payload):
    url = server + '/api/v1/models/' + str(modelID)
    headers = {
        "accept": "application/json",
        "Authorization": 'Bearer ' + token,
        "content-type": "application/json"
    }
    results = requests.patch(url, payload, headers=headers)
    return results.content

def installUniqueModels(server,token,existing_model_ids ):
    #load first sheet of excel into a dataframe
    df = pd.read_excel('Master Inventory - RMJC 4-15-2023.xlsx', index_col=0, sheet_name=0) 
    #extract the unique values of Summary Name which will become the unique models 

            
    M = snipeit.Models()
    sumRegex = re.compile(r"(\w+) - (\w+) - ([0123456789.x]+)")
    newSummaryRegex = re.compile(r"(\w+)-([0123456789.x]+)")
    newSummaryRegex = re.compile(r"(\w+)")
    for m in uniqueModels:
        summaryMatch = newSummaryRegex.match(m['name'])
        if(summaryMatch is None):
            #invalid name, skip it
            continue
        existing_id = existing_model_ids.get(m['name'])
        if(existing_id is None):
            print(f"creating model {m['name']}")
            r = M.create(server, token,  json.dumps(m))
        else:
            print(f"updating model {m['name']}")
            r = updateModel(existing_id,m)
            #r = M.updateModel(server, token, str(existing_id),json.dumps(m))
        if("error" in r):
            print(r)



if __name__ == "__main__":

    parser = argparse.ArgumentParser(description='import rmj spreadsheets into snipeit')
    parser.add_argument('password', nargs='?',
            help="snipeit database password")
    args = parser.parse_args()

    # the serial port used.
    conn, curr = connect(args.password)
    models = getModelsFromDB(curr,)
    installModelCustomFields(curr,conn,models)
    


