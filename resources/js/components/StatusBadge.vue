<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { ProjectStatus } from '@/types';

const props = defineProps<{
    status: ProjectStatus;
}>();

const label = computed(
    () => props.status.charAt(0).toUpperCase() + props.status.slice(1),
);
const variantClass = computed(
    () =>
        ({
            pending:
                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            processing:
                'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-200',
            completed:
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
            failed: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200',
        })[props.status],
);
</script>

<template>
    <Badge :class="variantClass">{{ label }}</Badge>
</template>
