<script setup lang="ts">
import { ref, computed } from 'vue';
import { Form } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { store } from '@/routes/todos';
import InputError from '@/components/InputError.vue';

interface Facility {
    id: number;
    name: string;
}

interface Props {
    open: boolean;
    facilities: Facility[];
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const activeTab = ref<'single' | 'bulk'>('single');

const handleClose = () => {
    emit('update:open', false);
};

const handleSuccess = () => {
    emit('success');
    handleClose();
};

const nextMondayFormatted = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + ((1 + 7 - d.getDay()) % 7 || 7));
    return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-xl">
            <DialogHeader>
                <DialogTitle class="text-2xl font-black uppercase tracking-tight text-blue-600">
                    New Weekly Todo
                </DialogTitle>
                <DialogDescription class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">
                    Plan a task for the coming week ({{ nextMondayFormatted }})
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="activeTab" class="w-full mt-2">
                <TabsList class="grid w-full grid-cols-2 mb-6">
                    <TabsTrigger value="single" class="text-xs font-bold uppercase tracking-widest">
                        Single Todo
                    </TabsTrigger>
                    <TabsTrigger value="bulk" class="text-xs font-bold uppercase tracking-widest">
                        Bulk Todo
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="single">
                    <Form :action="store().url" method="post" @success="handleSuccess" class="space-y-6"
                        #default="{ errors, processing }">
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest">Facility</Label>
                                <Select name="facility_id" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a facility" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="facility in facilities" :key="facility.id"
                                            :value="String(facility.id)">
                                            {{ facility.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors.facility_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest">Description</Label>
                                <textarea name="description" placeholder="Describe the task..."
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                                    required />
                                <InputError :message="errors.description" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="ghost" @click="handleClose"
                                class="font-bold uppercase tracking-widest text-[10px]">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="processing"
                                class="bg-blue-600 hover:bg-blue-700 font-black uppercase tracking-widest text-[10px]">
                                {{ processing ? 'Creating...' : 'Create Todo' }}
                            </Button>
                        </div>
                    </Form>
                </TabsContent>

                <TabsContent value="bulk">
                    <Form :action="store().url" method="post" @success="handleSuccess" class="space-y-6"
                        #default="{ errors, processing }">
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest">Facilities</Label>
                                <NativeSelect name="facility_ids[]" multiple size="5" required class="w-full">
                                    <NativeSelectOption v-for="facility in facilities" :key="facility.id"
                                        :value="String(facility.id)">
                                        {{ facility.name }}
                                    </NativeSelectOption>
                                </NativeSelect>
                                <p class="text-[10px] text-muted-foreground">Hold Ctrl/Cmd to select multiple facilities</p>
                                <InputError :message="errors.facility_ids" />
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest">Description</Label>
                                <textarea name="description" placeholder="Describe the task for all selected facilities..."
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                                    required />
                                <InputError :message="errors.description" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="ghost" @click="handleClose"
                                class="font-bold uppercase tracking-widest text-[10px]">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="processing"
                                class="bg-blue-600 hover:bg-blue-700 font-black uppercase tracking-widest text-[10px]">
                                {{ processing ? 'Creating...' : 'Create Todos' }}
                            </Button>
                        </div>
                    </Form>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>
