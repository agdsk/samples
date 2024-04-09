<div class="cool-row override-row">
    <a href="{{ route('overrides.edit', $Override->id) }}">
        <div class="cool-row__cell cool-row__cell--id">
            <div class="cool-row__cell__title">
                Override ID
            </div>
            <div class="cool-row__cell__value">
                {{ $Override->id }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--25">
            <div class="cool-row__cell__title">
                Location
            </div>
            <div class="cool-row__cell__value">
                {{ $Override->Location->name }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--20">
            <div class="cool-row__cell__title">
                Date
            </div>
            <div class="cool-row__cell__value">
                {{ $Override->date }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--25">
            <div class="cool-row__cell__title">
                Time
            </div>
            <div class="cool-row__cell__value">
                {{ \App\Models\Location::toTime($Override->start) }} to {{ \App\Models\Location::toTime($Override->end) }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--10">
            <div class="cool-row__cell__title">
                Stations
            </div>
            <div class="cool-row__cell__value">
                {{ $Override->stations }}
            </div>
        </div>
    </a>
</div>