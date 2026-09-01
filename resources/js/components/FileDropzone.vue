<script setup lang="ts">
import { Upload } from '@lucide/vue';
import { computed, ref } from 'vue';

const emit = defineEmits<{
    selected: [file: File | null];
}>();

const props = defineProps<{
    error?: string;
}>();

const file = ref<File | null>(null);
const localError = ref<string | null>(null);
const isDragging = ref(false);

const displayError = computed(() => localError.value ?? props.error);

function validate(selected: File | null): boolean {
    localError.value = null;

    if (!selected) {
        return true;
    }

    if (
        selected.type !== 'application/pdf' &&
        !selected.name.toLowerCase().endsWith('.pdf')
    ) {
        localError.value = 'Upload a PDF file.';
        return false;
    }

    if (selected.size > 10 * 1024 * 1024) {
        localError.value = 'PDF must be 10MB or smaller.';
        return false;
    }

    return true;
}

function setFile(selected: File | null) {
    if (!validate(selected)) {
        file.value = null;
        emit('selected', null);
        return;
    }

    file.value = selected;
    emit('selected', selected);
}

function handleInput(event: Event) {
    const input = event.target as HTMLInputElement;
    setFile(input.files?.[0] ?? null);
}

function handleDrop(event: DragEvent) {
    isDragging.value = false;
    setFile(event.dataTransfer?.files?.[0] ?? null);
}
</script>

<template>
    <label
        class="border-border hover:border-foreground/40 flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed px-6 py-8 text-center transition"
        :class="{ 'border-foreground/50 bg-muted': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
    >
        <input
            class="sr-only"
            type="file"
            accept="application/pdf,.pdf"
            @change="handleInput"
        />
        <Upload class="text-muted-foreground mb-3 size-8" />
        <span class="font-medium">{{
            file?.name ?? 'Drop PDF requirements here'
        }}</span>
        <span class="text-muted-foreground mt-1 text-sm"
            >PDF only, up to 10MB</span
        >
        <span v-if="displayError" class="text-destructive mt-3 text-sm">{{
            displayError
        }}</span>
    </label>
</template>
