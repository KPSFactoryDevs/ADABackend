<div class="hor-header header">
    <div class="container">
        <div class="d-flex" style="height: 74px;">
            <a style="max-width: 12%;margin-left: 8px;margin-top: 8px;" class="header-brand" href="http://127.0.0.1:8000/admin/dashboard">
                <img src="/images/png/KPSfactory.png" class="header-brand-img desktop-lgo" alt="Dayonelogo">
                <!-- <img src="/images/png/kpslogo.png" class="header-brand-img dark-logo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img mobile-logo" alt="Dayonelogo">
                <img src="/images/png/kpslogo.png" class="header-brand-img darkmobile-logo" alt="Dayonelogo"> -->
            </a>
            <div class="d-flex order-lg-2 my-auto ml-auto">
                <!--Nav-->
                <nav class="horizontalMenu clearfix">
                    <ul class="horizontalMenu-list">

                        <li class="c-sidebar-nav-dropdown">

                            <a href="{{route('admin.bilanci.bilancio.fileHome')}}" class="home" ><i class="feather feather-home hor-icon"></i> Home</a>
                        </li>

                        <li class="c-sidebar-nav-dropdown">

                            <a href="{{route('admin.dashboard')}}" class="panoramica" data-step="4"><i class="typcn typcn-image-outline"></i> Panoramica</a>
</li>

                        <li class="c-sidebar-nav-dropdown">
                            <a href="{{route('admin.bilanci.bilancio.datatable')}}" class="introduction-bilanci" data-step="2"><i class="feather feather-bar-chart hor-icon"></i> Bilanci</a>

                        </li>

                        <li class="c-sidebar-nav-dropdown">
                            <a href="{{route('admin.cr.centralerischi.datatable')}}" class="sub-icon" data-step="4"><i class="feather feather-pie-chart hor-icon"></i> Centrale Rischi</a>
                        </li>
                        <li class="c-sidebar-nav-dropdown">
                            <a href="{{route('admin.allerta.select')}}" class="sub-icon"><i class="feather feather-bell bell-icon"></i> Sistema Allerta</a>
                        </li>
                        <li class="c-sidebar-nav-dropdown">
                            <a href="{{route('admin.faq')}}" class="sub-icon"><i class="feather feather-edit edit-icon"></i> FAQ</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- SEARCH -->

            <div class="d-flex order-lg-2 my-auto ml-auto">
                <li class="nav-item dropdown">
                    <x-utils.link href="#" id="navbarDropdown" class="nav-link dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        <x-slot name="text">
                                @if(Auth::user()->profile_pic == null)
                                <img class="rounded-circle" style="height:30px;" src="{{ $logged_in_user->avatar }}" class="user-profile-image" />
                                @else
                                <img class="rounded-circle" style="height:60px;" id="profilePic" class="pic" src="/uploads/{{ Auth::user()->profile_pic }}">
                                @endif
                            <span class="caret"></span>
                        </x-slot>
                    </x-utils.link>
                    <!-- {{ $logged_in_user->name }} -->
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">

                        @if ($logged_in_user->isUser())
                        <x-utils.link :href="route('frontend.user.dashboard')" :active="activeClass(Route::is('frontend.user.dashboard'))" :text="__('Dashboard')" class="dropdown-item" />
                        @endif

                        <x-utils.link :href="route('frontend.user.account')" :active="activeClass(Route::is('frontend.user.account'))" :text="__('Impostazioni')" class="dropdown-item" />

                        <x-utils.link href="javascript:void(0);" onclick="javascript:introJs().start();" :text="__('Rivedi il tour?')" class="dropdown-item" />

                        <x-utils.link :text="__('Logout')" class="dropdown-item" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                            <x-slot name="text">
                                @lang('Logout')
                                <x-forms.post :action="route('frontend.auth.logout')" id="logout-form" class="d-none" />
                            </x-slot>
                        </x-utils.link>
                    </div>
                </li>


                <!-- IMPOSTAZIONI -->
            </div>
        </div>

    </div>
</div>

<a href="#top" id="back-to-top" style="display: none;"><span class="feather feather-chevrons-up"></span></a>
<link href="https://unpkg.com/intro.js/minified/introjs.min.css" type="text/css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/4.3.0/introjs-rtl.min.css" integrity="sha512-VwsKKwi99ZnRScgAkJ+ISGNolfoq+ic/mzJfhZWQ1xwfcbLZzLnHDoERYEppL25Okf+wEI/nDhHogudTa/YkWA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/4.3.0/introjs.min.css" integrity="sha512-YZO1kAqr8VPYJMaOgT4ZAIP4OeCuAWoZqgdvVYjeqyfieNWrUTzZrrxpgAdDrS7nV3sAVTKdP6MSKhqaMU5Q4g==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link href="https://unpkg.com/intro.js/themes/introjs-modern.css" rel="stylesheet">
<!-- Intro JS -->
<script type="text/javascript" src="https://unpkg.com/intro.js/minified/intro.min.js"></script>



<!-- Back to top -->
<a href="#top" id="back-to-top"><span class="feather feather-chevrons-up"></span></a>

