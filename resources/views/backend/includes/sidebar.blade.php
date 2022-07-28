<div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar" style="Background:#0d2f69;">
    <div class="c-sidebar-brand d-lg-down-none" style="background:#0a2538;">
        <h1 style="font-size:34px;"><span style="color:#b10000;">KPS</span> Factory</h1>
    </div>

    <ul class="c-sidebar-nav">
        <li class="c-sidebar-nav-item">
            <x-utils.link
                class="c-sidebar-nav-link"
                :href="route('admin.dashboard')"
                :active="activeClass(Route::is('admin.dashboard'), 'c-active')"
                icon="c-sidebar-nav-icon cil-speedometer"
                :text="__('Dashboard')" />
        </li>
			<li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Aziende')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.accounts.account.create')"
                            class="c-sidebar-nav-link"
                            :text="__('Nuova Azienda')" />
                    </li>
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.accounts.account.index')"
                            class="c-sidebar-nav-link"
                            :text="__('Tutte le Azienda')" />
                    </li>

                </ul>
            </li>

        @if ($logged_in_user->hasAllAccess())
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Bilanci')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.analysis.bilanci.create')"
                            class="c-sidebar-nav-link"
                            :text="__('Importa Bilancio XBRL')" />
                    </li>
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.analysis.bilanci.index')"
                            class="c-sidebar-nav-link"
                            :text="__('Lista Bilanci')" />
                    </li>

                </ul>
            </li>


			<li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Analisi di Bilancio')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.analisis.analisi.create')"
                            class="c-sidebar-nav-link"
                            :text="__('Nuova Analisi')" />
                    </li>
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.analisis.analisi.index')"
                            class="c-sidebar-nav-link"
                            :text="__('Tutte le Analisi')" />
                    </li>
                     <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.indicis.indici.index')"
                            class="c-sidebar-nav-link"
                            :text="__('Indici')" />
                    </li>
                </ul>
            </li>
<!--
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.pesis.pesi.index')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Peso')" />
                    </li>

                    <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.ranges.range.index')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Range')" />
                    </li>

                    <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.vocis.voci.index')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Voci di Bilancio')" />
                    </li>-->
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.cr.centralerischi.create')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Analisi dei Rischi')" />

            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.allerta.index')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Allerta')" />

            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Sistemi di allerta')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            :href="route('admin.sistemi.basic.index')"
                            icon="c-sidebar-nav-icon cil-list"
                            class="c-sidebar-nav-link"
                            :text="__('Basic')" />
                    <li class="c-sidebar-nav-dropdown">
                        <x-utils.link
                            :href="route('admin.sistemi.advanced.index')"
                            icon="c-sidebar-nav-icon cil-list"
                            class="c-sidebar-nav-link"
                            :text="__('Advanced')" />
                    </li>
                </ul>
            </li>



            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.cr.centralerischi.create')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Indici Personalizzati')" />
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.cr.centralerischi.create')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Fatturazione Elettronica')" />
            </li>
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.cr.centralerischi.create')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Contabilità')" />
            </li>
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    :href="route('admin.cr.centralerischi.create')"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-link"
                    :text="__('Documenti')" />
            </li>
        @endif
        @if (
            $logged_in_user->hasAllAccess() ||
            (
                $logged_in_user->can('admin.access.user.list') ||
                $logged_in_user->can('admin.access.user.deactivate') ||
                $logged_in_user->can('admin.access.user.reactivate') ||
                $logged_in_user->can('admin.access.user.clear-session') ||
                $logged_in_user->can('admin.access.user.impersonate') ||
                $logged_in_user->can('admin.access.user.change-password')
            )
        )
            <li class="c-sidebar-nav-title">@lang('System')</li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.auth.user.*') || Route::is('admin.auth.role.*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-user"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Access')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    @if (
                        $logged_in_user->hasAllAccess() ||
                        (
                            $logged_in_user->can('admin.access.user.list') ||
                            $logged_in_user->can('admin.access.user.deactivate') ||
                            $logged_in_user->can('admin.access.user.reactivate') ||
                            $logged_in_user->can('admin.access.user.clear-session') ||
                            $logged_in_user->can('admin.access.user.impersonate') ||
                            $logged_in_user->can('admin.access.user.change-password')
                        )
                    )
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('admin.auth.user.index')"
                                class="c-sidebar-nav-link"
                                :text="__('User Management')"
                                :active="activeClass(Route::is('admin.auth.user.*'), 'c-active')" />
                        </li>
                    @endif

                    @if ($logged_in_user->hasAllAccess())
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('admin.auth.role.index')"
                                class="c-sidebar-nav-link"
                                :text="__('Role Management')"
                                :active="activeClass(Route::is('admin.auth.role.*'), 'c-active')" />
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if ($logged_in_user->hasAllAccess())
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-list"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Logs')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('log-viewer::dashboard')"
                            class="c-sidebar-nav-link"
                            :text="__('Dashboard')" />
                    </li>
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('log-viewer::logs.list')"
                            class="c-sidebar-nav-link"
                            :text="__('Logs')" />
                    </li>
                </ul>
            </li>
        @endif
    </ul>

    <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
</div><!--sidebar-->
