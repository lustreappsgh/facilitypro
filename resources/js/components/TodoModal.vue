<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Form } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import TodoForm from '@/components/TodoForm.vue';

interface Facility {
    id: number;
    name: string;
}

interface Props {
    open: boolean;
    facilities: Facility[];
    selectedFacilityIds?: number[];
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}

const props = withDefaults(defineProps<Props>(), {
    selectedFacilityIds: () => [],
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
    action: '/todos',
    method: 'post',
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black uppercase tracking-tight">
                    New Todo
                </DialogTitle>
                <DialogDescription class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">
                    Plan weekly actions for your managed facilities
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-2 mb-6">
                    <TabsTrigger
                        value="single"
                        class="text-xs font-bold uppercase tracking-widest"
                        :disabled="selectedFacilityIds.length > 1"
                    >
                        Single Todo
                    </TabsTrigger>
                    <TabsTrigger value="bulk" class="text-xs font-bold uppercase tracking-widest">
                        Bulk Todo
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="single">
                    <Form v-bind="formConfig" @success="handleSuccess" #default="{ errors, processing }">
                        <TodoForm
                            :facilities="facilities"
                            :errors="errors"
                            :processing="processing"
                            :selected-facility-id="selectedSingleFacilityId"
                            :selected-facility-ids="selectedFacilityIds"
                            :show-cancel="true"
                            :on-cancel="handleClose"
                            cancel-href="#"
                            submit-label="Create Todo"
                        />
                    </Form>
                </TabsContent>

                <TabsContent value="bulk">
                    <Form v-bind="formConfig" @success="handleSuccess" #default="{ errors, processing }">
                        <TodoForm
                            :facilities="facilities"
                            :errors="errors"
                            :processing="processing"
                            facility-selection-mode="multiple"
                            :selected-facility-ids="selectedFacilityIds"
                            :show-cancel="true"
                            :on-cancel="handleClose"
                            cancel-href="#"
                            submit-label="Create Bulk Todo"
                        />
                    </Form>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>

