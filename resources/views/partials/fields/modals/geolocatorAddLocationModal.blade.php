<div class="modal modal-js modal-mask geolocator-add-location-modal-js">
    <div class="content">
        <div class="header">
            <span class="title title-js">Add a New Location</span>
            <a href="#" class="modal-toggle modal-toggle-js">
                <i class="icon icon-cancel"></i>
            </a>
        </div>
        <div class="body">
            <div class="form-group">
                {!! Form::label('locDesc', 'Location Name: ') !!}
                <input type="text" class="text-input location-desc-js" placeholder="Enter the Location name here">
            </div>
            <div class="form-group mt-xl">
                {!! Form::label('locType', 'Location Type: ') !!}
                {!! Form::select('loc_type', ['LatLon' => 'LatLon','UTM' => 'UTM','Address' => 'Address'], 'LatLon',
                    ['class' => 'single-select location-type-js']) !!}
            </div>

            <section class="lat-lon-switch-js">
                <div class="form-group mt-xl half pr-m">
                    {!! Form::label('latVal', 'Latitude: ') !!}
                    <input type="number" class="text-input location-lat-js" min=-90 max=90 step=".000001">
                </div>
                <div class="form-group mt-xl half pr-l">
                    {!! Form::label('lonVal', 'Longitude: ') !!}
                    <input type="number" class="text-input location-lon-js" min=-180 max=180 step=".000001">
                </div>
            </section>

            <section class="utm-switch-js hidden">
                <div class="form-group mt-xl">
                    {!! Form::label('zoneVal', 'Zone: ') !!}
                    <input type="text" class="text-input location-zone-js">
                </div>
                <div class="form-group mt-xl half pr-m">
                    {!! Form::label('eastVal', 'Easting: ') !!}
                    <input type="text" class="text-input location-east-js">
                </div>
                <div class="form-group mt-xl half pr-l">
                    {!! Form::label('northVal', 'Northing: ') !!}
                    <input type="text" class="text-input location-north-js">
                </div>
            </section>

            <section class="address-switch-js hidden">
                <div class="form-group mt-xl">
                    {!! Form::label('addrVal', 'Address: ') !!}
                    <input type="text" class="text-input location-addr-js">
                </div>
            </section>

            <div class="form-group mt-xxxl">
                <a href="#" class="btn add-new-location-js">Create Location</a>
            </div>
        </div>
    </div>
</div>