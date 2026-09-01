<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FileDropzone from '@/components/FileDropzone.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { h } from 'vue';

const props = defineProps<{
    defaultHourlyRate: number;
    defaultCountry: string;
    countries: Array<{
        code: string;
        name: string;
        currency: string;
    }>;
}>();

defineOptions({
    layout: (page: unknown) => {
        const breadcrumbs: BreadcrumbItem[] = [
            { title: 'Projects', href: '/projects' },
            { title: 'Upload', href: '/projects/upload' },
        ];

        return h(AppLayout, { breadcrumbs }, () => page);
    },
});

const form = useForm<{
    title: string;
    hourly_rate: number;
    country: string;
    requirements_pdf: File | null;
}>({
    title: '',
    hourly_rate: props.defaultHourlyRate,
    country: props.defaultCountry,
    requirements_pdf: null,
});

function submit() {
    form.post('/projects', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Upload Requirements" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">New Estimate</h1>
            <p class="text-muted-foreground text-sm">
                Upload a PDF requirements document to generate a budget
                breakdown.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Project Requirements</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="title">Project title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            required
                            placeholder="Client portal redesign"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="hourly_rate">Hourly rate</Label>
                            <Input
                                id="hourly_rate"
                                v-model.number="form.hourly_rate"
                                type="number"
                                min="1"
                                step="0.01"
                            />
                            <InputError :message="form.errors.hourly_rate" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Select v-model="form.country" name="country">
                                <SelectTrigger id="country" class="w-full">
                                    <SelectValue placeholder="Select country" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="country in props.countries"
                                        :key="country.code"
                                        :value="country.code"
                                    >
                                        {{ country.name }} ({{
                                            country.currency
                                        }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.country" />
                        </div>
                    </div>

                    <FileDropzone
                        :error="form.errors.requirements_pdf"
                        @selected="form.requirements_pdf = $event"
                    />

                    <div
                        v-if="form.progress"
                        class="bg-muted h-2 overflow-hidden rounded-full"
                    >
                        <div
                            class="bg-primary h-full transition-all"
                            :style="{
                                width: `${form.progress.percentage ?? 0}%`,
                            }"
                        />
                    </div>

                    <Button
                        class="w-full"
                        :disabled="form.processing || !form.requirements_pdf"
                    >
                        <Spinner v-if="form.processing" />
                        Generate Estimate
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
