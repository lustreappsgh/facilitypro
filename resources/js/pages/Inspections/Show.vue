<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useDateFormat } from '@/composables/useDateFormat';
import { my as inspectionsMy, edit as inspectionsEdit } from '@/routes/inspections';
import {
    ClipboardCheck, Building2, User, Calendar,
    FileText, AlertCircle, ArrowLeft, Pencil
} from 'lucide-vue-next';

interface Facility {
    id: number;
    name: string;
}

interface AddedBy {
    id: number;
    name: string;
}

interface Inspection {
    id: number;
    inspection_date: string;
    condition: string;
    comments?: string | null;
    image?: string | null;
    facility?: Facility | null;
    addedBy?: AddedBy | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    inspection: Inspection;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'My Inspections', href: inspectionsMy().url },
    { title: 'Inspection Details', href: '#' },
];

const { formatTableDate, formatDateTime, formatRelative } = useDateFormat();

const getConditionColor = (condition: string) => {
    const normalized = condition.toLowerCase();
    const colors: Record<string, string> = {
        good: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        bad: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        warning: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        critical: 'bg-rose-500/10 text-rose-600 border-rose-500/20',
    };
    return colors[normalized] || 'bg-muted text-muted-foreground';
};

const getConditionIcon = (condition: string) => {
    const normalized = condition.toLowerCase();
    if (normalized === 'good') return ClipboardCheck;
    return AlertCircle;
};

const ConditionIcon = computed(() => getConditionIcon(props.inspection.condition));
</script>

<template>

    <Head :title="`Inspection - ${inspection.facility?.name || 'Details'}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader
                title="Inspection details"
                subtitle="Review inspection details and condition updates."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="inspectionsMy().url">
                                <ArrowLeft class="mr-2 h-4 w-4" /> Back to inspections
                            </Link>
                        </Button>
                        <Button variant="secondary" as-child>
                            <Link :href="inspectionsEdit(inspection.id).url">
                                <Pencil class="mr-2 h-4 w-4" /> Edit
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- Hero Section -->
            <div class="relative overflow-hidden rounded-2xl border border-border bg-card p-8 shadow-lg">
                <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>

                <div class="flex items-start justify-between gap-6">
                    <div class="flex items-start gap-6">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                            <component :is="ConditionIcon" class="h-8 w-8 text-emerald-600" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <h1 class="text-3xl font-black font-display uppercase tracking-tight text-foreground">
                                    {{ inspection.facility?.name || 'Facility Inspection' }}
                                </h1>
                                <Badge variant="outline"
                                    :class="`rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider ${getConditionColor(inspection.condition)}`">
                                    {{ inspection.condition }}
                                </Badge>
                            </div>

                            <div
                                class="flex items-center gap-6 text-xs font-bold text-muted-foreground/60 uppercase tracking-widest">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    <span>{{ formatTableDate(inspection.inspection_date) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <User class="h-4 w-4" />
                                    <span>{{ inspection.addedBy?.name || 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3"></div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid gap-8 lg:grid-cols-[2fr_1fr]">
                <!-- Main Content -->
                <div class="space-y-6">
                    <!-- Comments -->
                    <Card class="border-border bg-card shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-3 text-sm font-black uppercase tracking-widest">
                                <FileText class="h-5 w-5 text-muted-foreground/60" />
                                Inspection Notes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p v-if="inspection.comments" class="text-sm leading-relaxed text-foreground/80">
                                {{ inspection.comments }}
                            </p>
                            <p v-else class="text-sm text-muted-foreground italic">
                                No comments provided for this inspection.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Image -->
                    <Card v-if="inspection.image" class="border-border bg-card shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-sm font-black uppercase tracking-widest">
                                Inspection Image
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <img :src="inspection.image" :alt="`Inspection of ${inspection.facility?.name}`"
                                class="w-full rounded-xl border border-border shadow-sm" />
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar Metadata -->
                <div class="space-y-6">
                    <!-- Facility Information -->
                    <Card class="border-border bg-card shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-3 text-sm font-black uppercase tracking-widest">
                                <Building2 class="h-5 w-5 text-muted-foreground/60" />
                                Facility Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50 mb-2">
                                    Facility Name
                                </p>
                                <p class="font-bold text-sm text-foreground">
                                    {{ inspection.facility?.name || 'Not specified' }}
                                </p>
                            </div>

                            <Separator />

                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50 mb-2">
                                    Condition Status
                                </p>
                                <Badge variant="outline"
                                    :class="`rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider ${getConditionColor(inspection.condition)}`">
                                    {{ inspection.condition }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Inspector Information -->
                    <Card class="border-border bg-card shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-3 text-sm font-black uppercase tracking-widest">
                                <User class="h-5 w-5 text-muted-foreground/60" />
                                Inspector
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50 mb-2">
                                    Conducted By
                                </p>
                                <p class="font-bold text-sm text-foreground">
                                    {{ inspection.addedBy?.name || 'Unknown' }}
                                </p>
                            </div>

                            <Separator />

                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50 mb-2">
                                    Inspection Date
                                </p>
                                <p class="font-bold text-sm text-foreground">
                                    {{ formatTableDate(inspection.inspection_date) }}
                                </p>
                                <p class="text-xs text-muted-foreground/60 mt-1">
                                    {{ formatRelative(inspection.inspection_date) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Timestamps -->
                    <Card class="border-border bg-card shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-3 text-sm font-black uppercase tracking-widest">
                                <Calendar class="h-5 w-5 text-muted-foreground/60" />
                                Record Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50 mb-2">
                                    Created
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTime(inspection.created_at) }}
                                </p>
                            </div>

                            <Separator />

                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50 mb-2">
                                    Last Updated
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTime(inspection.updated_at) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
