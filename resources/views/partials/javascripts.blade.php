@if(View::hasSection('javascripts'))
    @yield('javascripts')
@else
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
  <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

  <!-- Google reCAPTCHA -->
  <script type="text/javascript" src="https://www.google.com/recaptcha/api.js" async defer></script>
  <!-- Files for select 2-->
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.2/js/select2.min.js"></script>
  <!-- For Rich Text -->
  <script src="{{ env('BASE_URL') }}ckeditor/ckeditor.js"></script>
  <!-- For Schedule -->
  <script type="text/javascript" src="{{ env('BASE_URL') }}bower_components/moment/min/moment.min.js"></script>
  <script type="text/javascript" src="{{ env('BASE_URL') }}bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>

  <script src='{{ env('BASE_URL') }}bower_components/fullcalendar/dist/fullcalendar.js'></script>
  <!-- For Geolocator -->
  <script src="{{ env('BASE_URL') }}leaflet/leaflet.js"></script>
  <!-- For Documents -->
  <script src="{{ env('BASE_URL') }}fileUpload/js/vendor/jquery.ui.widget.js"></script>
  <script src="{{ env('BASE_URL') }}fileUpload/js/jquery.iframe-transport.js"></script>
  <script src="{{ env('BASE_URL') }}fileUpload/js/jquery.fileupload.js"></script>
  <!-- For Gallery -->
  <script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
  <script type="text/javascript" src="{{ env('BASE_URL') }}slick/slick/slick.min.js"></script>
  <!-- For Playlist and Video -->
  <script type="text/javascript" src="{{ env('BASE_URL') }}jplayer/jquery.jplayer.min.js"></script>
  <script type="text/javascript" src="{{ env('BASE_URL') }}jplayer/jplayer.playlist.min.js"></script>
  <!-- For 3D Model -->
  <script type="text/javascript" src="{{ env('BASE_URL') }}jsc3d/jsc3d.js"></script>
  <script type="text/javascript" src="{{ env('BASE_URL') }}jsc3d/jsc3d.webgl.js"></script>
  <script type="text/javascript" src="{{ env('BASE_URL') }}jsc3d/jsc3d.touch.js"></script>
@endif