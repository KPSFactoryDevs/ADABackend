<!doctype html>
<html lang="{{ htmlLang() }}" @langrtl dir="rtl" @endlangrtl>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title')</title>
  <meta name="description" content="@yield('meta_description', appName())">
  <meta name="author" content="@yield('meta_author', 'Anthony Rappa')">
  @yield('meta')
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  @stack('before-styles')
  <link href="{{asset('/plugins/fancyuploder/fancy_fileupload.css')}}" rel="stylesheet" />
  <link href="{{ mix('css/backend.css') }}" rel="stylesheet">
  <link href="{{ asset('css/animated.css') }}" media="screen" rel="stylesheet" type="text/css">
  <link href="{{ asset('css/dark.css') }}" media="screen" rel="stylesheet" type="text/css">
  <link href="{{ asset('css/icons.css') }}" media="screen" rel="stylesheet" type="text/css">
  <link href="{{ asset('css/sidemenu.css') }}" media="screen" rel="stylesheet" type="text/css">
  <link href="{{ asset('css/skin-modes.css') }}" media="screen" rel="stylesheet" type="text/css">
  <link href="{{ asset('css/style.css') }}" media="screen" rel="stylesheet" type="text/css">
  <link href="{{ asset('css/css-rtl.css') }}" media="screen" rel="stylesheet" type="text/css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


  <livewire:styles />
  @stack('after-styles')


  <script>
  $( function() {
    $( document ).tooltip();
  } );
  </script>
  <style>
  label {
    display: inline-block;
    width: 5em;
  }
  </style>
</head>

<body class="c-app">

  <!-- Start of kpsfactory Zendesk Widget script -->
  <script id="ze-snippet" src="https://static.zdassets.com/ekr/snippet.js?key=67f646ad-bb40-4ca7-bde9-7e3b71d7801b"></script>
  <!-- End of kpsfactory Zendesk Widget script -->


  <div class="c-wrapper c-fixed-components">
    @include('frontend.includes.nav')
    @include('includes.partials.read-only')
    @include('includes.partials.logged-in-as')

    <div class="c-body">
      <main class="">
        <div class="container-fluid">
          <div class="fade-in">
            @include('includes.partials.messages')
            @yield('content')
          </div>
          <!--fade-in-->
        </div>
        <!--container-fluid-->
      </main>
    </div>
    <!--c-body-->

    @include('backend.includes.footer')
  </div>
  <!--c-wrapper-->

  @stack('before-scripts')
  <script type="text/javascript" src="{{asset('plugins/jquery/jquery.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/moment/moment.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/bootstrap/popper.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/bootstrap/js/bootstrap.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/othercharts/jquery.sparkline.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/circle-progress/circle-progress.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/select2/select2.full.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/sidemenu/sidemenu.js')}}"></script>

  <script type="text/javascript" src="{{asset('js/custom.js')}}"></script>

  <script type="text/javascript" src="{{('/plugins/chart/chart.bundle.js')}}"></script>

  <script src="{{ mix('js/manifest.js') }}"></script>
  <script src="{{ mix('js/vendor.js') }}"></script>
  <script src="{{ mix('js/backend.js') }}"></script>
  <script type="text/javascript" src="{{ asset('js/jquery.multi-select.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/accordion.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/apexchart-custom.js')}}"></script>

  <script type="text/javascript" src="{{ asset('js/app-calendar.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/app-calendar-events.js')}}"></script>

  <script type="text/javascript" src="{{ asset('js/charts.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/chat.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/chat2.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/construction.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/contact.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/cookie.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/countdown.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/datatables.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/daterange.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/dragula.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/echarts.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/file-upload.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/filupload.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/form-editor.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/form-editor2.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/form-elements.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/form-wizard.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/formelementadvanced.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/forms.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/fullcalendar.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/gallery.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/image-comparision.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/img-crop.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/index5.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/jvectormap.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/livechat.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/map-leafleft.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/mapelmaps.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/morris.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/newsticker.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/popover.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/rangeslider.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/rounded-barchart.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/select2.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/session.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/sparkline.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/sticky.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/sweet-alert.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/sticky2.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/tabs.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/timeline.js')}}"></script>
  <script type="text/javascript" src="{{ asset('js/widgets.js')}}"></script>

  <script type="text/javascript" src="{{asset('plugins/chart/chart.bundle.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/chart/utils.js')}}"></script>
  <script type="text/javascript" src="{{asset('js/chart.js')}}"></script>
  <livewire:scripts />
  @stack('after-scripts')
  <script type="text/javascript" src="{{asset('plugins/flot/jquery.flot.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/flot/jquery.flot.fillbetween.js')}}"></script>
  <script type="text/javascript" src="{{asset('plugins/flot/jquery.flot.pie.js')}}"></script>
  <script type="text/javascript" src="{{asset('js/flot.js')}}"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>


  @yield('footerscripts')


  
</body>

</html>