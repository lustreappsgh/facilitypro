<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Form } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import MaintenanceRequestForm from '@/components/MaintenanceRequestForm.vue';

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface Props {
    open: boolean;
    facilities: Facility[];
    requestTypes: RequestType[];
    selectedFacilityIds?: number[];
    redirectTo?: string;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}

const props = withDefaults(defineProps<Props>(), {
    selectedFacilityIds: () => [],
    redirectTo: undefined,
});

const emit = defineEmits<Emits>();

const activeTab = ref<'single' | 'bulk'>('single');
const selectedSingleFacilityId = computed(() =>
    props.selectedFacilityIds.length === 1 ? props.selectedFacilityIds[0] : null,
);

watch(
    () => [props.open, props.selectedFacilityIds.length],
    ([open, length]) => {
        if (!open) {
            return;
        }
        activeTab.value = length > 1 ? 'bulk' : 'single';
    },
    { immediate: true },
);

const handleClose = () => {
    emit('update:open', false);
};

const handleSuccess = () => {
    emit('success');
    handleClose();
};

const formConfig = {
    action: '/maintenance',
    method: 'post',
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black uppercase tracking-tight">
                    New Maintenance Request
                </DialogTitle>
                <DialogDescription class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">
                    Log maintenance issues and estimate impacts
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-2 mb-6">
                    <TabsTrigger
                        value="single"
                        class="text-xs font-bold uppercase tracking-widest"
                        :disabled="selectedFacilityIds.length > 1"
                    >
                        Single Request
                    </TabsTrigger>
                    <TabsTrigger value="bulk" class="text-xs font-bold uppercase tracking-widest">
                        Bulk Request
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="single">
                    <Form v-bind="formConfig" @success="handleSuccess" #default="{ errors, processing }">
                        <input v-if="redirectTo" type="hidden" name="redirect_to" :value="redirectTo" />
                        <MaintenanceRequestForm :facilities="facilities" :request-types="requestTypes" :errors="errors"
                            :processing="processing" :selected-facility-id="selectedSingleFacilityId"
                            :selected-facility-ids="selectedFacilityIds" :show-cancel="true" :on-cancel="handleClose"
                            cancel-href="#" submit-label="Submit Request" />
                    </Form>
                </TabsContent>

                <TabsContent value="bulk">
                    <Form v-bind="formConfig" @success="handleSuccess" #default="{ errors, processing }">
                        <input v-if="redirectTo" type="hidden" name="redirect_to" :value="redirectTo" />
                        <MaintenanceRequestForm :facilities="facilities" :request-types="requestTypes" :errors="errors"
                            :processing="processing" facility-selection-mode="multiple"
                            :selected-facility-ids="selectedFacilityIds" :show-cancel="true" :on-cancel="handleClose"
                            cancel-href="#" submit-label="Submit Bulk Request" />
                    </Form>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>
