<script setup lang="ts">
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const {
    title = '',
    description = '',
    showLogo = true,
    variant = 'simple',
} = defineProps<{
    title?: string;
    description?: string;
    showLogo?: boolean;
    variant?: 'simple' | 'card';
}>();
</script>

<template>
    <component
        :is="variant === 'card' ? AuthCardLayout : AuthSimpleLayout"
        :title="title"
        :description="description"
        :show-logo="showLogo"
    >
        <slot />
    </component>
</template>
