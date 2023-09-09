
<script nonce="{{ csrf_token() }}">


if (window.jQuery) {
    // jQuery is available.
    // Print the jQuery version, e.g. "1.0.0":
    console.log("jQuery version =",window.jQuery.fn.jquery);
}

function round2(x) {
    return Number.parseFloat(x).toFixed(2);
}

function calcBDF(){
    const bdf_el = document.getElementById("_snipeit_bdf_8");
    const height_el = document.getElementById("_snipeit_width_5");
    const width_el = document.getElementById("_snipeit_thickness_4");
    const length_el = document.getElementById("_snipeit_length_7");
    const name_el = document.getElementById("name");

    const l = parseFloat(document.getElementById("_snipeit_length_7").value);
    const h = parseFloat(document.getElementById("_snipeit_width_5").value); //thickness
    const w = parseFloat(document.getElementById("_snipeit_thickness_4").value);

    const bdf = round2(((w*h)/12) * l);

    if(bdf > 0)
        bdf_el.value = bdf;
    return bdf;
}


function calcName(){
    const height_el = document.getElementById("_snipeit_width_5");
    const width_el = document.getElementById("_snipeit_thickness_4");
    const length_el = document.getElementById("_snipeit_length_7");
    const name_el = document.getElementById("name");  // will be WxHxL

    const l = document.getElementById("_snipeit_length_7").value;
    const h = document.getElementById("_snipeit_width_5").value;
    const w = document.getElementById("_snipeit_thickness_4").value;

    // Use WxHxL to match Hundegger
    // const name = [round2(w),round2(h),round2(l)].join("x");
    const name = [w,h,l].join("x");
    name_el.value = name;
    return name;
}

function calcPurchaseCost(){
    const purchase_cost_el = document.getElementById("purchase_cost");
    const bdf_cost = 0.0 + parseFloat(document.getElementById("_snipeit_bdf_cost_10").value);
    const bdf = 0.0 + parseFloat(document.getElementById("_snipeit_bdf_8").value);
    const freight_cost = 0.0 + parseFloat(document.getElementById("_snipeit_freight_11").value); 
    const price = round2((freight_cost+bdf_cost)*bdf);

    if(price > 0)
        purchase_cost_el.value = price;
    return price;
}


function autoSetModel(){
    //find or create the right model for this size of timber
    //TBD
}


$( "#_snipeit_length_7,#_snipeit_width_5,#_snipeit_thickness_4" ).on( "change", function() {
    calcBDF();
    calcName();
    calcPurchaseCost();
    autoSetModel();
} );


$( "#_snipeit_bdf_cost_10,#_snipeit_freight_11,#_snipeit_bdf_8" ).on( "change", function() {
    calcPurchaseCost();
} );


</script>

