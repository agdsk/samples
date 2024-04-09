@if(Auth::check())
    <header class="header">
        <div class="header__interior">
            <nav class="navigation">
                <table>
                    <tr>
                        <td style="position: relative; height: 100%">
                            &nbsp;
                            <div class="navigation__scroller">
                                @if (Auth::user()->isManager())
                                    <ul>
                                        @if (Auth::user()->isManager())
                                            <li><a href="{{ route('locations.index') }}">Locations</a></li>
                                        @endif

                                        @if (Auth::user()->isManager())
                                            <li><a href="{{ route('users.index') }}">Users</a></li>
                                        @endif

                                        @if (Auth::user()->isAdmin())
                                            <li><a href="{{ route('promotions.index') }}">Promo Codes</a></li>
                                        @endif

                                        @if (Auth::user()->isAdmin())
                                            <li><a href="{{ route('brands.index') }}">Brands</a></li>
                                        @endif
                                    </ul>
                                @endif

                                @if (Auth::user()->isManager())
                                    <ul>
                                        <li>
                                            <a>Tools</a>

                                            <ul>
                                                <li>
                                                    <a href="{{ action('ToolsController@findInvalidReservations') }}">Find Invalid Reservations</a>
                                                </li>
                                                <li>
                                                    <a href="{{ action('ToolsController@findDuplicateLocations') }}">Find Duplicate Locations</a>
                                                </li>
                                                <li>
                                                    <a href="{{ action('ToolsController@massOverrideForm') }}">Create Overrides</a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                @endif

                                @if (Auth::user()->isAmbassador())
                                    <ul>
                                        @if (Auth::user()->role == 10)
                                            <li><a href="{{ action('ReservationsController@index') }}">Appointments</a>
                                            </li>
                                        @endif

                                        <li>
                                            <a>View Scripts</a>

                                            <ul>
                                                @foreach (\App\Models\Script::all() as $Script)
                                                    <li>
                                                        <a href="{{ action('ScriptsController@show', $Script->id) }}">{{ $Script->name }}</a>
                                                    </li>
                                                @endforeach

                                                @if (Auth::user()->isAdmin())
                                                    <li>
                                                        <a href="{{ action('ScriptsController@index') }}">Edit Scripts</a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>

                                        <li><a href="{{ action('ReminderController@index') }}">Send Reminder</a></li>
                                    </ul>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <ul class="navigation__footer">
                                <li>
                                    <a href="{{ action('AccountController@password') }}">
                                        {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                                        <small>{{ Auth::user()->role_name }}</small>
                                    </a>
                                </li>
                                <li><a href="{{ action('AuthController@logout') }}">Logout</a></li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </nav>

            <img class="hamburger" src="{{ asset('images/hamburger.png') }}">
            {{--<div class="quicknav">System Management</div>--}}
            <a href="{{ url('/') }}" class="logo-link"><img class="logo" src="{{ asset('images/stadium-white.png') }}"></a>
        </div>
    </header>
@endif