<button {{ $attributes->merge(['type' => 'button', 'class' => 'button button--soft']) }}>
    {{ $slot }}
</button>
