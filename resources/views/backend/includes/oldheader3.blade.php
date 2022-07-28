
<div class="hor-header header">
    <div class="container">
        <div class="d-flex">
            <a style="max-width: 10%" class="header-brand" href="{{ route('admin.dashboard') }}">
                <img src="/images/png/kpslogo.png" class="header-brand-img desktop-lgo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img dark-logo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img mobile-logo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img darkmobile-logo" alt="Dayonelogo">
            </a>
            <div class="mt-0">
                <form class="form-inline">
                    <div class="search-element">
                        <input type="search" class="form-control header-search" placeholder="Cerca…" aria-label="Cerca" tabindex="1">
                        <button class="btn btn-primary-color" >
                            <i class="feather feather-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <!-- SEARCH -->   

            <div class="d-flex order-lg-2 my-auto ml-auto">
                <a class="nav-link my-auto icon p-0 nav-link-lg d-md-none navsearch" href="#" data-toggle="search">
                    <i class="feather feather-search search-icon header-icon"></i>
                </a>

                  <!-- <a href=""><img height="50px" src="" class="user-profile-image rounded-circle" /></a> -->
                 
                  <li class="nav-item dropdown">
                        <x-utils.link
                            href="#"
                            id="navbarDropdown"
                            class="nav-link dropdown-toggle"
                            role="button"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                            v-pre
                        >
                            <x-slot name="text">
                                <img class="rounded-circle" style="max-height: 20px" src="{{ $logged_in_user->avatar }}" />
                                {{ $logged_in_user->name }} <span class="caret"></span>
                            </x-slot>
                        </x-utils.link>

                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                           
                            @if ($logged_in_user->isUser())
                                <x-utils.link
                                    :href="route('frontend.user.dashboard')"
                                    :active="activeClass(Route::is('frontend.user.dashboard'))"
                                    :text="__('Dashboard')"
                                    class="dropdown-item"/>
                            @endif

                            <x-utils.link
                                :href="route('frontend.user.account')"
                                :active="activeClass(Route::is('frontend.user.account'))"
                                :text="__('Impostazioni')"
                                class="dropdown-item" />

                            <x-utils.link
                                :text="__('Logout')"
                                class="dropdown-item"
                                onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                <x-slot name="text">
                                    @lang('Logout')
                                    <x-forms.post :action="route('frontend.auth.logout')" id="logout-form" class="d-none" />
                                </x-slot>
                            </x-utils.link>
                        </div>
                    </li>


                <!-- IMPOSTAZIONI
                    <div class="dropdown header-message">
                    <button class="btn btn-primary"><i  class="fa fa-cog"></i></button>
                </div> 
            -->
            </div>
        </div>

    </div>
</div>

<div class="sticky">
    <div class="horizontal-main hor-menu clearfix">
        <div class="horizontal-mainwrapper container clearfix p-0">
            <!--Nav-->
            <nav class="horizontalMenu clearfix">
                <ul class="horizontalMenu-list">

                    <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            :href="route('admin.dashboard')"
                            icon="feather feather-home hor-icon"
                            class="panoramica"
                            data-step='4'
                            :text="__('Panoramica')" />
                    </li>
                    

                    <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            :href="route('admin.bilanci.bilancio.datatable')"
                            icon="feather feather-bar-chart hor-icon"
                            class="introduction-bilanci"
                       {{-- data-toggle="tooltip"
                            title="ciao!" --}}
                            data-step='2'
                            :text="__('Bilanci')" />
                    </li>
                   
                    <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            :href="route('admin.cr.centralerischi.datatable')"
                            icon="feather feather-pie-chart hor-icon"
                            class="sub-icon"
                            data-step='4'
                            :text="__('Centrale Rischi')" />
                    </li>
                    <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            :href="route('admin.allerta.select')"
                            icon="feather feather-bell bell-icon"
                            class="sub-icon"
                            :text="__('Sistema Allerta')" />
                    </li>
                   <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            href="/admin/faq"
                            icon="feather feather-edit edit-icon"
                            class="sub-icon"
                            :text="__('FAQ')" />
                    </li>
                </ul>
            </nav>
        
                <!-- <div class="pull-right">
                    <a href="faq"><i class="fas fa-question-circle" style="font-size: 20px" title="FAQ"></i></a>
                </div> -->
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
