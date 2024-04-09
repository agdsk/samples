<div class="cool-row user-row">
    <a href="{{ route('promotions.edit', $Promotion->id) }}">
        <div class="cool-row__cell cool-row__cell--20">
            <div class="cool-row__cell__title">
                Code
            </div>
            <div class="cool-row__cell__value">
                {{ $Promotion->code }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--25">
            <div class="cool-row__cell__title">
                Location
            </div>
            <div class="cool-row__cell__value">
                {{ $Promotion->Location->branded_name }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--30">
            <div class="cool-row__cell__title">
                Date Range
            </div>
            <div class="cool-row__cell__value">
                {{ \App\Models\Location::toDate($Promotion->start) }} &mdash; {{ \App\Models\Location::toDate($Promotion->end) }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--10">
            <div class="cool-row__cell__title">
                Seats Unlocked
            </div>
            <div class="cool-row__cell__value">
                {{ $Promotion->size }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--10">
            <div class="cool-row__cell__title">
                Limit
            </div>
            <div class="cool-row__cell__value">
                {{ $Promotion->limit }}
            </div>
        </div>
    </a>
</div>