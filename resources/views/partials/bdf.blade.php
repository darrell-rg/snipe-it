
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
    const height_el = document.getElementById("_snipeit_height_5");
    const width_el = document.getElementById("_snipeit_width_4");
    const length_el = document.getElementById("_snipeit_length_7");
    const name_el = document.getElementById("name");

    const l = parseFloat(document.getElementById("_snipeit_length_7").value);
    const h = parseFloat(document.getElementById("_snipeit_height_5").value); //thickness
    const w = parseFloat(document.getElementById("_snipeit_width_4").value);

    const bdf = round2(((w*h)/12) * l);

    if(bdf > 0)
        bdf_el.value = bdf;
    // Use WxHxL to match Hundegger
    name_el.value = [width_el.value,height_el.value,length_el.value].join("x");
}

function calcPurchaseCost(){
    const purchase_cost_el = document.getElementById("purchase_cost");
    const bdf_cost = 0.0 + parseFloat(document.getElementById("_snipeit_bdf_cost_10").value);
    const bdf = 0.0 + parseFloat(document.getElementById("_snipeit_bdf_8").value);
    const freight_cost = 0.0 + parseFloat(document.getElementById("_snipeit_freight_11").value); 
    const price = round2((freight_cost+bdf_cost)*bdf);

    if(price > 0)
        purchase_cost_el.value = price;
}


$( "#_snipeit_length_7,#_snipeit_height_5,#_snipeit_width_4" ).on( "change", function() {
    calcBDF()
} );

$( "#_snipeit_bdf_cost_10,#_snipeit_freight_11,#_snipeit_bdf_8" ).on( "change", function() {
    calcPurchaseCost()
} );


</script>

