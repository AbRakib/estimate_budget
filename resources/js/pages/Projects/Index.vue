<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Ellipsis, Eye, Plus, Trash2 } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PaginatedProjects, Project } from '@/types';
import { h } from 'vue';

defineProps<{
    projects: PaginatedProjects;
}>();

defineOptions({
    layout: (page: unknown) => {
        const breadcrumbs: BreadcrumbItem[] = [
            { title: 'Projects', href: '/projects' },
        ];

        return h(AppLayout, { breadcrumbs }, () => page);
    },
});

const money = (value: number, currency = 'USD') =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);

function deleteProject(project: Project) {
    if (!window.confirm(`Delete "${project.title}"?`)) {
        return;
    }

    router.delete(`/projects/${project.id}`);
}
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
                <p class="text-muted-foreground text-sm">
                    Past requirement estimates and their current processing
                    status.
                </p>
            </div>
            <Button as-child>
                <Link href="/projects/upload">
                    <Plus class="size-4" />
                    New Estimate
                </Link>
            </Button>
        </div>

        <div class="border-border overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th class="w-16 px-4 py-3 text-left font-medium">SL</th>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Hours</th>
                        <th class="px-4 py-3 text-right font-medium">Days</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="w-16 px-4 py-3 text-right font-medium">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(project, index) in projects.data"
                        :key="project.id"
                        class="border-border border-t"
                    >
                        <td class="text-muted-foreground px-4 py-3">
                            {{ (projects.meta?.from ?? 1) + index }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ project.title }}
                        </td>
                        <td class="text-muted-foreground px-4 py-3">
                            {{ project.created_at_formatted }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="project.status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span v-if="project.estimate">
                                {{ project.estimate.total_hours }}
                            </span>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span v-if="project.estimate">
                                {{ project.estimate.total_days }}
                            </span>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span v-if="project.estimate">{{
                                money(
                                    project.estimate.total_cost,
                                    project.estimate.currency,
                                )
                            }}</span>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                    >
                                        <Ellipsis class="size-4" />
                                        <span class="sr-only"
                                            >Open actions</span
                                        >
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-40">
                                    <DropdownMenuItem as-child>
                                        <Link :href="`/projects/${project.id}`">
                                            <Eye class="size-4" />
                                            Details
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @click="deleteProject(project)"
                                    >
                                        <Trash2 class="size-4" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </td>
                    </tr>
                    <tr v-if="projects.data.length === 0">
                        <td
                            colspan="8"
                            class="text-muted-foreground px-4 py-10 text-center"
                        >
                            No projects yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="projects.links?.length" class="flex flex-wrap gap-2">
            <Link
                v-for="link in projects.links"
                :key="link.label"
                :href="link.url ?? '#'"
                class="border-border rounded-md border px-3 py-1 text-sm"
                :class="{
                    'bg-primary text-primary-foreground': link.active,
                    'pointer-events-none opacity-50': !link.url,
                }"
                v-html="link.label"
            />
        </div>
    </div>
</template>
