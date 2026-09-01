<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ProjectEstimate } from '@/types';

defineProps<{
    estimate?: ProjectEstimate | null;
    hourlyRate: number;
}>();

const money = (value: number, currency = 'USD') =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Estimate Summary</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-4">
            <div>
                <p class="text-muted-foreground text-sm">Hours</p>
                <p class="text-2xl font-semibold">
                    {{ estimate?.total_hours ?? 0 }}
                </p>
            </div>
            <div>
                <p class="text-muted-foreground text-sm">Days</p>
                <p class="text-2xl font-semibold">
                    {{ estimate?.total_days ?? 0 }}
                </p>
            </div>
            <div>
                <p class="text-muted-foreground text-sm">Budget</p>
                <p class="text-2xl font-semibold">
                    {{
                        money(
                            estimate?.total_cost ?? 0,
                            estimate?.currency ?? 'USD',
                        )
                    }}
                </p>
            </div>
            <div>
                <p class="text-muted-foreground text-sm">Rate</p>
                <p class="text-2xl font-semibold">
                    {{ money(hourlyRate, estimate?.currency ?? 'USD') }}/hr
                </p>
            </div>
        </CardContent>
    </Card>
</template>
