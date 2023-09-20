
<?php
$printerIP = '192.168.10.77';
$result = '';
$zpl= '';
$apiUrl = config('app.url') . "/hardware/". $asset->id ."/zpl_png" 

?>

<div class="col-md-12" style="padding-top: 5px; max-height:300px" >
    <img src="{{$apiUrl}}" class="img-thumbnail" 
    alt="Zebra Label preview for {{ $asset->getDisplayNameAttribute() }}">

{{--hidden pre tag for debugging zpl--}}  
@if(env('APP_ENV')=='developmentjjjj') 
<pre style="text-align:left">
Reply from {{$printerIP}}: 
{{$result}}

ZPl Sent:
{{$zpl}}
</pre>
@endif

</div>