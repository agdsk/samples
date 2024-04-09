<div class="cool-row brand-row">
    <a href="{{ route('brands.edit', $Brand->id) }}">
        <div class="cool-row__cell cool-row__cell--id">
            <div class="cool-row__cell__title">
                Brand ID
            </div>
            <div class="cool-row__cell__value">
                {{ $Brand->id }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--25">
            <div class="cool-row__cell__title">
                Brand Name
            </div>
            <div class="cool-row__cell__value">
                {{ $Brand->name }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--20">
            <div class="cool-row__cell__title">
                Slug
            </div>
            <div class="cool-row__cell__value">
                {{ $Brand->slug }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--20">
            <div class="cool-row__cell__title">
                Logo
            </div>
            <div class="cool-row__cell__value">
                <img src="{{ env('APP_CONSUMER_SITE') }}/assets/logos/{{ $Brand->slug }}.png">
            </div>
        </div>
    </a>
</div>