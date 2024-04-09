<div class="checkblock">
    @if ($type == 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0">
    @endif

    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" id="{{ md5($text) }}" {{ old($name) == $value || $active ? 'checked' : '' }}>
        
    <label class="checkblock__label" for="{{ md5($text) }}">
        <img src="{{ asset('images/checkmark.png') }}">
        {{ $text }}
    </label>
</div>