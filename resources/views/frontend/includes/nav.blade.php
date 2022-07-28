
<div class="hor-header header" style="background: content-box;">
    <div class="container">
        <div class="flex">
            <a style="max-width: 15%;  display:block; margin:0px auto; text-align:center;" class="header-brand" href="{{ route('admin.dashboard') }}">
                <img src="/images/png/kpslogo.png" class="header-brand-img desktop-lgo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img dark-logo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img mobile-logo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img darkmobile-logo" alt="Dayonelogo">
            </a>
            

            <div class="d-flex order-lg-2 my-auto ml-auto">
                <a class="nav-link my-auto icon p-0 nav-link-lg d-md-none navsearch" href="#" data-toggle="search">
                    <i class="feather feather-search search-icon header-icon"></i>
                </a>

                
                <!-- IMPOSTAZIONI
                    <div class="dropdown header-message">
                    <button class="btn btn-primary"><i  class="fa fa-cog"></i></button>
                </div> 
            -->
            </div>
        </div>

    </div>
</div>
  

 <!-- Back to top -->
 <a href="#top" id="back-to-top"><span class="feather feather-chevrons-up"></span></a>

<!-- Intro JS -->
<link href="https://unpkg.com/intro.js/minified/introjs.min.css"  type="text/css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/4.3.0/introjs-rtl.min.css" integrity="sha512-VwsKKwi99ZnRScgAkJ+ISGNolfoq+ic/mzJfhZWQ1xwfcbLZzLnHDoERYEppL25Okf+wEI/nDhHogudTa/YkWA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/4.3.0/introjs.min.css" integrity="sha512-YZO1kAqr8VPYJMaOgT4ZAIP4OeCuAWoZqgdvVYjeqyfieNWrUTzZrrxpgAdDrS7nV3sAVTKdP6MSKhqaMU5Q4g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="https://unpkg.com/intro.js/themes/introjs-modern.css" rel="stylesheet">
    <!-- Intro JS -->
<script type="text/javascript" src="https://unpkg.com/intro.js/minified/intro.min.js"></script>

  <script>
  introJs().setOptions({
    showProgress: true,
    scrollToElement: true,
    positionPrecedence: ["right"],
    exitOnOverlayClick: false,
    doneLabel: "Prossima pagina",
    nextLabel: "Prossimo",
    prevLabel: "Indietro",
    showStepNumbers: true,
    steps: [{
        intro: "<h3>Benvenuto!</h3><br><span>Vuoi iniziare il tour guidato?</span>",
        
      },
      {
        element: document.querySelector('.introduction-bilanci'),
        intro: "Questo indica tutti i bilanci analizzati",

      },

    ]
  }).stop();
</script> 
