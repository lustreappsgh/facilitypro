<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <img
        src="/brand/anagkazo_logo.png"
        alt="Anagkazo"
        class="h-9 w-auto object-contain"
        v-bind="$attrs"
    />
</template>
