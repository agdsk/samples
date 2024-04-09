<div class="cool-row location-row">
    <a href="{{ route('locations.show', $Location->id) }}">
        <div class="cool-row__cell cool-row__cell--id">
            <div class="cool-row__cell__title">
                Location ID
            </div>
            <div class="cool-row__cell__value">
                <img src="{{ $Location->statusImage }}"> {{ $Location->id }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--20">
            <div class="cool-row__cell__title">
                Brand
            </div>
            <div class="cool-row__cell__value">
                {{ $Location->Brand->name }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--25">
            <div class="cool-row__cell__title">
                Location Name
            </div>
            <div class="cool-row__cell__value">
                {{ $Location->branded_name }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--25">
            <div class="cool-row__cell__title">
                City, Region
            </div>
            <div class="cool-row__cell__value">
                {{ $Location->city }}@if ($Location->region), {{ $Location->region }}@endif
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--10">
            <div class="cool-row__cell__title">
                Country
            </div>
            <div class="cool-row__cell__value">
                {{ $Location->country }}
            </div>
        </div>
    </a>
</div>