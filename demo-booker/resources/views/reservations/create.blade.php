@extends('layouts.standard', ['layout_class' => 'light'])

@section('page-control-left')
    <a class="button" href="{{ action('ReservationsController@location', [$Location->id]) }}"><</a>
@stop

@section('page-control-middle')
    <h1>Register User</h1>
@stop

@section('page-control-right')
    &nbsp;
@stop

@section('main')
    @include('components/page-control')

    {{-- Health and Safety Modal -------------------------------------------------------}}

    <div class="modal" id="health-and-safety-modal">
        <div class="modal__title">ACME HEALTH & SAFETY ACKNOWLEDGEMENT</div>

        <p>Some people may experience symptoms during and after using virtual reality, including motion sickness, reduced coordination, disorientation, visual abnormalities, or other discomfort.</p>
        <p>If you experience any of these symptoms, stop immediately, advise the attendant, and do not engage in activities that require unimpaired balance and coordination until you have fully recovered.</p>
        <p>And please remember that while virtual reality may look and sound very real, virtual objects cannot (yet) support your weight or the weight of real world objects.</p>
        <p>See <a href="http://acme.com/warnings" target="_blank">acme.com/warnings</a> for the latest health and safety information</p>

        <div class="modal__title">ACME GESUNDHEITS - UND SICHERHEITSHINWEIS</div>

        <p>Bei einigen Personen kann es während und nach der Nutzung von Virtual Reality zu Symptomen wie z.B. Schwindelgefühl, Übelkeit, verminderter Koordination, Orientierungsstörungen, Sehstörungen oder sonstigen Beschwerden kommen.</p>
        <p>Sollten Symptome dieser Art bei Ihnen auftreten, stellen Sie die Nutzung sofort ein, benachrichtigen Sie das Personal, und führen Sie keine Tätigkeiten durch, für die uneingeschränkte Koordination und Gleichgewicht erforderlich sind, bis Sie sich wieder vollständig erholt haben.</p>
        <p>Und bitte denken Sie daran, dass selbst wenn Virtual Reality sich sehr real anfühlen und anhören kann, virtuelle Gegenstände (bisher) weder Ihr Gewicht noch das Gewicht von realen Gegenständen tragen können.</p>
        <p>Unsere aktuellen Sicherheits- und Gesundheitschutzhinweise finden Sie unter: <a href="http://acme.com/warnings" target="_blank">acme.com/warnings</a>.</p>

        <div class="modal__title">AVERTISSEMENT SANTE ET SECURITE D’ACME</div>

        <p>Certaines personnes peuvent ressentir des effets secondaires pendant et après l’utilisation de la réalité virtuelle, y compris le mal des transports, une coordination réduite des mouvements, une désorientation, des anomalies visuelles ou d’autres effets gênants.</p>
        <p>Si vous ressentez l’un de ces effets secondaires, arrêtez immédiatement l’utilisation de la réalité virtuelle, avertissez le préposé, ne vous livrez pas à des activités qui nécessitent un parfait équilibre et une bonne coordination des mouvements jusqu’à ce que vous ayez intégralement récupéré.</p>
        <p>Veuillez-vous souvenir que même si la réalité virtuelle peut vous sembler très réelle, les objets virtuels ne peuvent pas (pour le moment) supporter votre poids ou celui des objets du monde réel. </p>
        <p>Consultez <a href="http://acme.com/warnings" target="_blank">acme.com/warnings</a> pour les informations récentes de santé et de sécurité.</p>
    </div>

    <form method="POST" class="form-narrow" action="{{ action('ReservationsController@saveReservation') }}">
        <fieldset class="form-fieldset">
            <input type="hidden" name="location_id" value="{{ $Location->id }}">
            @include('components/field-input', ['classes' => 'width-full', 'name' => 'first_name', 'value' => '', 'placeholder' => $strings['reservation__form__first']])
            @include('components/field-input', ['classes' => 'width-full', 'name' => 'last_name',  'value' => '', 'placeholder' => $strings['reservation__form__last']])
            @include('components/field-input', ['classes' => 'width-full', 'name' => 'email',      'value' => '', 'placeholder' => $strings['reservation__form__email']])

            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="time" value="{{ $time }}">
        </fieldset>

        @if ($Location->country != 'USA')
            <fieldset class="card">
                <label style="float: right; width: 90%;" class="subtle" for="subscribed">
                    <b>{{ $strings['subscribe__title'] }}</b><br>
                    {{ $strings['subscribe__description'] }}
                </label>
                <input type="checkbox" style="float: left; margin-top: 5px" id="subscribed" name="subscribed">
            </fieldset>
        @endif

        <fieldset class="card">
            <p class="subtle">
                {{ $strings['reservation__safety_1'] }}

                <a class="health-and-safety-modal-link" style="text-decoration: underline; cursor: pointer;">{{ $strings['reservation__safety_link_text'] }}</a>

                {{ $strings['reservation__safety_2'] }}

                <a href="https://www.acme.com/en-us/legal/privacy-policy/" target="_blank">{{ $strings['reservation__privacy_text'] }}</a>.</p>
        </fieldset>

        <fieldset class="form-fieldset centered-contents">
            <button class="button button--taller" type="submit">{{ $strings['location__modal__nextButton']['reserve'] }}</button>
        </fieldset>
    </form>
@stop
