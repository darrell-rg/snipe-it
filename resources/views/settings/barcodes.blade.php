@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{  trans('admin/settings/general.barcode_title') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('settings.index') }}" class="btn btn-primary"> {{ trans('general.back') }}</a>
@stop


{{-- Page content --}}
@section('content')

    <style>
        .checkbox label {
            padding-right: 40px;
        }
    </style>


    {{ Form::open(['method' => 'POST', 'files' => false, 'autocomplete' => 'off', 'class' => 'form-horizontal', 'role' => 'form' ]) }}
    <!-- CSRF Token -->
    {{csrf_field()}}

    <div class="row">
        <div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">


            <div class="panel box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">
                        <i class="fas fa-barcode" aria-hidden="true"></i> {{ trans('admin/settings/general.barcodes') }}
                    </h2>
                </div>
                <div class="box-body">


                    <div class="col-md-11 col-md-offset-1">

                    @if ($is_gd_installed)
                        <!-- qr code -->
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <label class="form-control">
                                        {{ Form::checkbox('qr_code', '1', old('qr_code', $setting->qr_code),array('aria-label'=>'qr_code')) }}
                                        {{ trans('admin/settings/general.display_qr') }}
                                    </label>
                                </div>
                            </div>

                            <!-- square barcode type -->
                            <div class="form-group{{ $errors->has('barcode_type') ? ' has-error' : '' }}">
                                <div class="col-md-3">
                                    {{ Form::label('barcode_type', trans('admin/settings/general.barcode_type')) }}
                                </div>
                                <div class="col-md-9">
                                    {!! Form::barcode_types('barcode_type', old('barcode_type', $setting->barcode_type), 'select2 col-md-4') !!}
                                    {!! $errors->first('barcode_type', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                </div>
                            </div>

                            <!-- barcode -->
                            <div class="form-group">

                                <div class="col-md-9 col-md-offset-3">
                                    <label class="form-control">
                                        {{ Form::checkbox('alt_barcode_enabled', '1', old('alt_barcode_enabled', $setting->alt_barcode_enabled),array( 'aria-label'=>'alt_barcode_enabled')) }}
                                        {{ trans('admin/settings/general.display_alt_barcode') }}
                                    </label>
                                </div>
                            </div>

                            <!-- barcode type -->
                            <div class="form-group{{ $errors->has('alt_barcode') ? ' has-error' : '' }}">
                                <div class="col-md-3">
                                    {{ Form::label('alt_barcode', trans('admin/settings/general.alt_barcode_type')) }}
                                </div>
                                <div class="col-md-9">
                                    {!! Form::alt_barcode_types('alt_barcode', old('alt_barcode', $setting->alt_barcode), 'select2 col-md-4') !!}
                                    {!! $errors->first('barcode_type', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                </div>
                            </div>
                        @else
                            <span class="help-block col-md-offset-3 col-md-12">
                                {{ trans('admin/settings/general.php_gd_warning') }}
                                <br>
                                {{ trans('admin/settings/general.php_gd_info') }}
                  </span>
                    @endif

                    <!-- qr text -->
                        <div class="form-group {{ $errors->has('qr_text') ? 'error' : '' }}">
                            <div class="col-md-3">
                                {{ Form::label('qr_text', trans('admin/settings/general.qr_text')) }}
                            </div>
                            <div class="col-md-9">
                                @if ($setting->qr_code == 1)
                                    {{ Form::text('qr_text', Request::old('qr_text', $setting->qr_text), array('class' => 'form-control','placeholder' => 'Property of Your Company',
                                    'rel' => 'txtTooltip',
                                    'title' =>'Extra text that you would like to display on your labels. ',
                                    'data-toggle' =>'tooltip',
                                    'data-placement'=>'top')) }}
                                    {!! $errors->first('qr_text', '<span class="alert-msg" aria-hidden="true">:message</span>') !!}
                                @else
                                    {{ Form::text('qr_text', Request::old('qr_text', $setting->qr_text), array('class' => 'form-control', 'disabled'=>'disabled','placeholder' => 'Property of Your Company')) }}
                                    <p class="help-block">{{ trans('admin/settings/general.qr_help') }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Nuke barcode cache -->
                        <div class="form-group">
                            <div class="col-md-3">
                                {{ Form::label('purge_barcodes', 'Purge Barcodes') }}
                            </div>
                            <div class="col-md-9" id="purgebarcodesrow">
                                <a class="btn btn-default btn-sm pull-left" id="purgebarcodes" style="margin-right: 10px;">
                                    {{ trans('admin/settings/general.barcode_delete_cache') }}</a>
                                <span id="purgebarcodesicon"></span>
                                <span id="purgebarcodesresult"></span>
                                <span id="purgebarcodesstatus"></span>
                            </div>
                            <div class="col-md-9 col-md-offset-3">
                                <div id="purgebarcodesstatus-error" class="text-danger"></div>
                            </div>
                            <div class="col-md-9 col-md-offset-3">
                                <p class="help-block">{{ trans('admin/settings/general.barcodes_help') }}</p>
                            </div>

                        </div>

                        <div class="form-group">
                            <div class="col-md-9 col-md-offset-3">
                                <label class="form-control">
                                    {{ Form::checkbox('use_zpl', '1', old('use_zpl', $setting->use_zpl),array('aria-label'=>'use_zpl')) }}
                                     <!-- {{ trans('admin/settings/general.display_qr') }}-->
                                     Use ZPL printer
                                </label>
                            </div>
                        </div>

                        <!-- zebra printer IP -->
                        <div class="form-group {{ $errors->has('zpl_printer_address') ? 'error' : '' }}">
                            <div class="col-md-3">
                                {{ Form::label('zpl_printer_address', "ZPL Printer IP Address") }}
                            </div>
                            <div class="col-md-9">
                                @if ($setting->use_zpl == 1)
                                    {{ Form::text('zpl_printer_address', Request::old('zpl_printer_address', $setting->zpl_printer_address), array('class' => 'form-control','placeholder' => '192.168.10.77',
                                    'rel' => 'txtTooltip',
                                    'title' =>'IP address of ZPL printer. ',
                                    'data-toggle' =>'tooltip',
                                    'data-placement'=>'top')) }}
                                    {!! $errors->first('zpl_printer_address', '<span class="alert-msg" aria-hidden="true">:message</span>') !!}
                                @else
                                    {{ Form::text('zpl_printer_address', Request::old('zpl_printer_address', $setting->zpl_printer_address), array('class' => 'form-control', 'disabled'=>'disabled','placeholder' => '192.168.10.77')) }}
                                    <p class="help-block">{{ trans('admin/settings/general.qr_help') }}</p>
                                @endif
                            </div>
                        </div>


                        <!-- Webserial Testing ,   -->
                        <div class="form-group">
                            <div class="col-md-3">
                                {{ Form::label('serialtestrow', "Web Serial Test") }}
                            </div>
                            <div class="col-md-9" id="serialtestrow">
                                <a class="btn btn-default btn-sm pull-left" id="serialtest" style="margin-right: 10px;">Serial Test</a>
                                <span id="serialtesticon"></span>
                                <span id="serialtestresult"></span>
                                <span id="serialteststatus"></span>
                            </div>
                            <div class="col-md-9 col-md-offset-3">
                                <div id="serialteststatus-error" class="text-danger"></div>
                            </div>
                            <div class="col-md-9 col-md-offset-3">
                                <p class="help-block">chrome://device-log/</p>
                                <p class="serialtestdata"></p>
                            </div>

                        </div>


                        <!-- webusb Testing  -->
                        <div class="form-group">
                            <div class="col-md-3">
                                {{ Form::label('usbtestrow', "Web USB Test") }}
                            </div>
                            <div class="col-md-9" id="usbtestrow">
                            ZPL to print:<br>
                            <textarea id="printContent" rows="10" cols="50">
^XA
^FX Top section with logo, name and address.
^CF0,60
^FO50,50^GB100,100,100^FS
^FO75,75^FR^GB100,100,100^FS
^FO93,93^GB40,40,40^FS
^FO220,50^FDWeb USB test ^FS
^CF0,190
^FO100,300^FDWEB^FS
^FO100,600^FDUSB^FS
^FX Third section with bar code.
^BY5,2,270
^FO100,800^BC^FDMorgan^FS
^FX Write the RFID in hex 96 bits is 12 bytes
^WT0^FH^FD_00_00_00_MORGAN000^FS
^XZ
                                </textarea>
                                <a class="btn btn-default btn-sm pull-left" id="usbtest" onclick="requestDevice()" style="margin-right: 10px;">Select Printer</a>
                                <a class="btn btn-default btn-sm pull-left" id="usbdevice" style="display:none"></a>
                                <span id="usbtesticon"></span>
                                <span id="usbtestresult"></span>
                                <span id="usbteststatus"></span>
                            </div>
                            <div class="col-md-9 col-md-offset-3">
                                <div id="usbteststatus-error" class="text-danger"></div>
                            </div>
                            <div class="col-md-9 col-md-offset-3">

                            </div>

                        </div>





	</script>

                    </div>

                </div> <!--/.box-body-->
                <div class="box-footer">
                    <div class="text-left col-md-6">
                        <a class="btn btn-link text-left" href="{{ route('settings.index') }}">{{ trans('button.cancel') }}</a>
                    </div>
                    <div class="text-right col-md-6">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check icon-white" aria-hidden="true"></i> {{ trans('general.save') }}</button>
                    </div>

                </div>
            </div> <!-- /box -->
        </div> <!-- /.col-md-8-->
    </div> <!-- /.row-->

    {{Form::close()}}

@stop

@push('js')

    <script nonce="{{ csrf_token() }}">
        // Delete barcodes
        $("#purgebarcodes").click(function(){
            $("#purgebarcodesrow").removeClass('text-success');
            $("#purgebarcodesrow").removeClass('text-danger');
            $("#purgebarcodesicon").html('');
            $("#purgebarcodesstatus").html('');
            $('#purgebarcodesstatus-error').html('');
            $("#purgebarcodesicon").html('<i class="fas fa-spinner spin"></i> {{ trans('admin/settings/general.barcodes_spinner') }}');
            $.ajax({
                url: '{{ route('api.settings.purgebarcodes') }}',
                type: 'POST',
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                data: {},
                dataType: 'json',

                success: function (data) {
                    console.dir(data);
                    $("#purgebarcodesicon").html('');
                    $("#purgebarcodesstatus").html('');
                    $('#purgebarcodesstatus-error').html('');
                    $("#purgebarcodesstatus").removeClass('text-danger');
                    $("#purgebarcodesstatus").addClass('text-success');
                    if (data.message) {
                        $("#purgebarcodesstatus").html('<i class="fas fa-check text-success"></i> ' + data.message);
                    }
                },

                error: function (data) {

                    $("#purgebarcodesicon").html('');
                    $("#purgebarcodesstatus").html('');
                    $('#purgebarcodesstatus-error').html('');
                    $("#purgebarcodesstatus").removeClass('text-success');
                    $("#purgebarcodesstatus").addClass('text-danger');
                    $("#purgebarcodesicon").html('<i class="fas fa-exclamation-triangle text-danger"></i>');
                    $('#purgebarcodesstatus').html('Files could not be deleted.');
                    if (data.responseJSON) {
                        $('#purgebarcodesstatus-error').html('Error: ' + data.responseJSON.messages);
                    } else {
                        console.dir(data);
                    }

                }


            });
        });

// make sure you are in the "lp" group on linux so you have printer permissions
// sudo usermod -a -G lp darrell

// look at devices with lsusb:
// >lsusb -v -d 14ae:0000

// you may need to unload the kernal driver to fix this error:
//      Failed to claim interface 0: Device or resource busy (16)
// find the driver with >usb-devices 
// driver name is usblp, use modprobe to stop it
// >sudo modprobe -r usblp

// you may also need to stop cups if cups is grabing that device
// sudo service cups stop
// sudo snap stop cups
// sudo snap disable cups

// how to add a udev rule for plugdev.   Maybe not needed?
// sudo vim /etc/udev/rules.d/printonix.rules
// #ID 14ae:0000 Printronix Inc.
// SUBSYSTEM=="usb", ATTRS{idVendor}=="14ae", ATTR{idProduct}=="0000", MODE="0660", GROUP="plugdev"

// how to turn on usb in chromium snap:
// sudo snap connect chromium:raw-usb

if ("usb" in navigator) {
    console.log("The Web USB API is supported.");
}

async function requestDevice() {
    try {
        const device = await navigator.usb.requestDevice({ filters: [{ vendorId: 0x14ae }] })
        const elem = document.querySelector('#usbdevice');
        elem.style = '';
        console.log("device is ",device)
        elem.textContent = `Print ZPL to device '${device.vendorId}:${device.productId}'`;
        elem.onclick = () => testPrint(device);
    } catch (e) {
        console.error(e);
    }
}

async function testPrint(device) {
    var zplString = document.getElementById("printContent").value + "\n";
        await device.open();
        await device.selectConfiguration(1);
        await device.claimInterface(0);
        await device.transferOut(
            device.configuration.interfaces[0].alternate.endpoints.find(obj => obj.direction === 'out').endpointNumber,
            new Uint8Array(
                new TextEncoder().encode(zplString)
            ),
        );
    await device.close();
}



function connectUSB() {
    navigator.usb.getDevices()
        .then(devices => {
            if (devices.length > 0) {
                console.log("found these usb devices ",devices)
            }
            else{
                console.log("found zero usb devices")
            }
        })
        .catch(error => { console.log(error); });
}

    
async function sendSerialData(){
    while (port.readable) {
        const reader = port.readable.getReader();
        try {
            while (true) {
            const { value, done } = await reader.read();
            if (done) {
                // |reader| has been canceled.
                break;
            }
            // Do something with |value|...
            }
        } catch (error) {
            // Handle |error|...
        } finally {
            reader.releaseLock();
        }
    }
}

if ("serial" in navigator) {
    console.log("The Web Serial API is supported.");
    $("#serialtestdata").html("The Web Serial API is supported.");
    navigator.serial.addEventListener("connect", (e) => {
    // Connect to `e.target` or add it to a list of available ports.
    });

    navigator.serial.addEventListener("disconnect", (e) => {
    // Remove `e.target` from the list of available ports.
    });

    navigator.serial.getPorts().then((ports) => {
    // Initialize the list of available ports with `ports` on page load.
    });

    $("#serialtest").click( () => {
        console.log("Starting Serial test.");
        const usbVendorId = 0x0403; //ftdi is 0403
        //Bus 001 Device 011: ID 0403:6010 Future Technology Devices International, Ltd FT2232C Dual port
        //may need to plug/unplug for chrome to find it
        // may need sudo snap connect chromium:raw-usb
        // plug/unplug event should show in chrome://device-log/
        const filters = [
            { usbVendorId: 0x0403, usbProductId: 0x6010 },
            { usbVendorId: 0x2341, usbProductId: 0x0001 }
        ];
        navigator.serial
            // .requestPort({ filters }})
            .requestPort()
            .then((port) => {
                // Connect to `port` or add it to the list of available ports.
                const reader = port.readable.getReader();

                // Turn on Data Terminal Ready (DTR) signal.
                //await port.setSignals({ dataTerminalReady: true });

                //const writer = port.writable.getWriter();

                //const data = new Uint8Array([104, 101, 108, 108, 111]); // hello
                //await writer.write(data);


                const textEncoder = new TextEncoderStream();
                const writableStreamClosed = textEncoder.readable.pipeTo(port.writable);

                const writer = textEncoder.writable.getWriter();
                
                // await writer.write("~HI\n");

                // Allow the serial port to be closed later.
                writer.releaseLock();

                // Listen to data coming from the serial device.
                // while (true) {
                //     const { value, done } = await reader.read();
                //     if (done) {
                //         // Allow the serial port to be closed later.
                //         reader.releaseLock();
                //         break;
                //     }
                //     // value is a Uint8Array.
                //     console.log(value);
                // }



            })
            .catch((e) => {
                // The user didn't select a port.
            });
    });
}
else{
    $("#serialtestdata").html("Web Serial API NOT supported in this browser.");
}




    </script>

@endpush
