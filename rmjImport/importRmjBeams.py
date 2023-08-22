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
import pprint
pp = pprint.PrettyPrinter(indent=4)
import snipeit
# from snipeit import Models

server='https://dev2.sawpit.app'
token='eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMmMzNDM4NzVhMGRmNjk4ZGIwNzVlOWIwNjU1ZWU3NmZlOGQ0Y2ZkODg4OGVmZmRjMmNjM2VkNmI5MDczMzc5ZmZjZGQ4MzI5NjM4OGUxZTkiLCJpYXQiOjE2OTI2NTIyNTYuNDg5MTEsIm5iZiI6MTY5MjY1MjI1Ni40ODkxMTQsImV4cCI6MjE2NjAzNzg1Ni40ODYwMjgsInN1YiI6IjEiLCJzY29wZXMiOltdfQ.akmvQemXZNbQfsn5d78WdVgp-deWB89Oy8yQkqFh4UL_AShm9GM0QKZRYdA6rHJew09ewqmF1yayCbCw2uAXnx9dRfCin-cq1xd3WmdEKp7-5uww-KpvhYUdWAkrSx8agEE1GtbawF4G8XC5p3D61zZmMeUQQ_aZKCAlsrGYgtlt_Oz-iBRY_i9FIxsm9FP2F2shwPjdPrRVAEB-YZ9hXwK29chVD2Igv9JHBod0LGlccrUgQO9NBPMe6SdD8HYYljtmX9F3gIsxWF5GzF-Y9Ne78ywD0cu1G42oEzRVpTVLabTH--HaqzK8tXyf_Oc6aehlxQdozgSpw3nlj2PJZ5y1avz6gv_--IbM427w9nPy6NCUkNqGzvk7SGC7R7KKRM8j80IN5KeUUKjpYyr1iXsYJal0Vj_IfniQbWaKJ5uzUBaRwDV1s7lHsuJ8kSPhzY5Ms2KOnocs3BIRNnAQUU5fLv8fMtZbseZiZT8fL8NAZYwu_dGzWslu596CUWCsIQlYwnXj6edyiAMRJHayCwzWA4qZqxvKLz44A-ig9WRFNxO-deTsdLmzAofUrn9UY6PV99UZZE0bxeAlwi7DEOeCO8vlkK40nuNrFvskiilB8lvYl1Ri3sKmQmWY0tqVFcSYx4fUHmqlSTgwc3wK4xn98dVW0LX1s7sYggRZVqc'




def installUniqeModels():
    #load first sheet of excel into a dataframe
    df = pd.read_excel('Master Inventory - RMJC 4-15-2023.xlsx', index_col=0, sheet_name=0) 
    #extract the unique values of Summary Name which will become the unique models 
