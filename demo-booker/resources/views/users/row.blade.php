<div class="cool-row user-row">
    <a href="{{ route('users.edit', $User->id) }}">
        <div class="cool-row__cell cool-row__cell--id">
            <div class="cool-row__cell__title">
                User ID
            </div>
            <div class="cool-row__cell__value">
                <img src="{{ $User->statusImage }}"> {{ $User->id }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--30">
            <div class="cool-row__cell__title">
                User Name
            </div>
            <div class="cool-row__cell__value">
                {{ $User->name }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--40">
            <div class="cool-row__cell__title">
                Email
            </div>
            <div class="cool-row__cell__value">
                {{ $User->email }}
            </div>
        </div>
        <div class="cool-row__cell cool-row__cell--20">
            <div class="cool-row__cell__title">
                Role
            </div>
            <div class="cool-row__cell__value">
                {{ $User->roleName }}
            </div>
        </div>
    </a>
</div>