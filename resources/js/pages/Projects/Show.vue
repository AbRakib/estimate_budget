<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { RotateCcw, Trash2 } from '@lucide/vue';
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';
import { computed, h, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import EstimateSummaryCard from '@/components/EstimateSummaryCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Project } from '@/types';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
);

const props = defineProps<{
    project: { data: Project } | Project;
}>();

defineOptions({
    layout: (page: unknown) => {
        const breadcrumbs: BreadcrumbItem[] = [
            { title: 'Projects', href: '/projects' },
            { title: 'Estimate', href: '#' },
        ];

        return h(AppLayout, { breadcrumbs }, () => page);
    },
});

const project = ref(
    'data' in props.project ? props.project.data : props.project,
);
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;
let pollTimer: ReturnType<typeof window.setInterval> | null = null;

const isWaiting = computed(() =>
    ['pending', 'processing'].includes(project.value.status),
);
const categoryHours = computed(() => {
    return project.value.features.reduce<Record<string, number>>(
        (hours, feature) => {
            hours[feature.category] =
                (hours[feature.category] ?? 0) + feature.estimated_hours;
            return hours;
        },
        {},
    );
});

const money = (value: number, currency = 'USD') =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);

async function poll() {
    if (!isWaiting.value) {
        stopPolling();
        return;
    }

    const response = await fetch(`/projects/${project.value.id}/status`, {
        headers: { Accept: 'application/json' },
    });

    if (response.ok) {
        const payload = await response.json();
        project.value = payload.data;
    }
}

function startPolling() {
    stopPolling();
    pollTimer = window.setInterval(poll, 3000);
}

function stopPolling() {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
}

function retry() {
    router.post(`/projects/${project.value.id}/retry`);
}

function destroyProject() {
    router.delete(`/projects/${project.value.id}`);
}

function renderChart() {
    if (!chartCanvas.value || project.value.status !== 'completed') {
        return;
    }

    chart?.destroy();
    chart = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: Object.keys(categoryHours.value),
            datasets: [
                {
                    label: 'Hours',
                    data: Object.values(categoryHours.value),
                    backgroundColor: ['#2563eb', '#059669', '#f59e0b'],
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
        },
    });
}

onMounted(() => {
    if (isWaiting.value) {
        startPolling();
    }
    void nextTick(renderChart);
});

watch(isWaiting, (waiting) => {
    if (waiting) {
        startPolling();
    } else {
        stopPolling();
        void nextTick(renderChart);
    }
});

watch(categoryHours, () => void nextTick(renderChart), { deep: true });

onUnmounted(() => {
    stopPolling();
    chart?.destroy();
});
</script>

<template>
    <Head :title="project.title" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ project.title }}
                    </h1>
                    <StatusBadge :status="project.status" />
                </div>
                <p class="text-muted-foreground text-sm">
                    Created {{ project.created_at_formatted }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <Link href="/projects">Back</Link>
                </Button>
                <Button variant="destructive" @click="destroyProject">
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <Card v-if="isWaiting">
            <CardContent
                class="flex min-h-64 flex-col items-center justify-center gap-3 text-center"
            >
                <Spinner />
                <p class="font-medium">Analyzing requirements...</p>
                <p class="text-muted-foreground text-sm">
                    This page checks for updates every 3 seconds.
                </p>
            </CardContent>
        </Card>

        <Card v-else-if="project.status === 'failed'">
            <CardContent
                class="flex min-h-52 flex-col items-center justify-center gap-4 text-center"
            >
                <p class="font-medium">Estimate failed</p>
                <p class="text-muted-foreground max-w-xl text-sm">
                    {{ project.failure_reason }}
                </p>
                <Button @click="retry">
                    <RotateCcw class="size-4" />
                    Retry
                </Button>
            </CardContent>
        </Card>

        <template v-else>
            <EstimateSummaryCard
                :estimate="project.estimate"
                :hourly-rate="project.hourly_rate"
            />

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <Card>
                    <CardHeader>
                        <CardTitle>Feature Breakdown</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="border-border overflow-hidden rounded-lg border"
                        >
                            <table class="w-full text-sm">
                                <thead class="bg-muted text-muted-foreground">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left font-medium"
                                        >
                                            Feature
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-medium"
                                        >
                                            Category
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-medium"
                                        >
                                            Complexity
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right font-medium"
                                        >
                                            Hours
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right font-medium"
                                        >
                                            Cost
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="feature in project.features"
                                        :key="feature.id"
                                        class="border-border border-t align-top"
                                    >
                                        <td class="px-4 py-3">
                                            <p class="font-medium">
                                                {{ feature.name }}
                                            </p>
                                            <p
                                                class="text-muted-foreground mt-1 text-sm"
                                            >
                                                {{ feature.description }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 capitalize">
                                            {{ feature.category }}
                                        </td>
                                        <td class="px-4 py-3 capitalize">
                                            {{ feature.complexity }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ feature.estimated_hours }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{
                                                money(
                                                    feature.estimated_cost,
                                                    project.estimate?.currency,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        class="bg-muted/50 border-border border-t font-semibold"
                                    >
                                        <td class="px-4 py-3" colspan="3">
                                            Grand total
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ project.estimate?.total_hours }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{
                                                money(
                                                    project.estimate
                                                        ?.total_cost ?? 0,
                                                    project.estimate?.currency,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Hours by Category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-72">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                        <p
                            v-if="project.estimate?.ai_notes"
                            class="text-muted-foreground mt-5 text-sm"
                        >
                            {{ project.estimate.ai_notes }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </template>
    </div>
</template>
