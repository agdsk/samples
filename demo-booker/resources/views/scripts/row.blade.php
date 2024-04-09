<div class="cool-row script-row">
    <a href="{{ route('scripts.edit', $Script->id) }}">
        <div class="cool-row__cell cool-row__cell--id">
            <div class="cool-row__cell__title">
                Script ID
            </div>
            <div class="cool-row__cell__value">
                {{ $Script->id }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--85">
            <div class="cool-row__cell__title">
                Script Name
            </div>
            <div class="cool-row__cell__value">
                {{ $Script->name }}
            </div>
        </div>
    </a>
</div>