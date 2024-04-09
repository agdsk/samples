<div class="checkbutton">
    @if ($type == 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0">
    @endif

    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" id="{{ md5($text) }}" {{ old($name) == $value || $active ? 'checked' : '' }}>
        
    <label class="checkbutton__label button" for="{{ md5($text) }}">
        {{ $text }}
    </label>
</div>