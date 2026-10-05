@props(['keys' => []])
<script>
    window.i18n = Object.assign(window.i18n || {}, @js(collect($keys)->mapWithKeys(fn ($key) => [$key => __($key)])));
</script>
