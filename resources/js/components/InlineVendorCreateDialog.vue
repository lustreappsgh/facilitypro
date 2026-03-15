<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as vendorsStore } from '@/routes/vendors';
import type { AppPageProps } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { watch } from 'vue';

interface VendorOption {
    id: number;
    name: string;
}

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    (event: 'created', vendor: VendorOption): void;
}>();

const page = usePage<AppPageProps>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    service_type: '',
    status: 'active',
    redirect_to: '',
});

const closeDialog = (): void => {
    open.value = false;
    form.reset();
    form.clearErrors();
};

const submit = (): void => {
    form.redirect_to = window.location.pathname + window.location.search;
    form.post(vendorsStore().url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
};

watch(
    () => page.props.flash?.created_vendor,
    (createdVendor) => {
        if (!createdVendor || typeof createdVendor !== 'object') {
            return;
        }

        emit('created', createdVendor as VendorOption);
        form.reset();
        form.clearErrors();
    },
);
</script>

<template>
    <div class="flex items-center gap-2">
        <Button type="button" variant="outline" size="sm" class="h-9 px-3" @click="open = true">
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Add vendor
        </Button>

        <Dialog v-model:open="open">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create vendor</DialogTitle>
                    <DialogDescription>
                        Add a vendor without leaving this workflow.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="inline_vendor_name">Name</Label>
                        <Input id="inline_vendor_name" v-model="form.name" placeholder="Vendor name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="inline_vendor_email">Email</Label>
                            <Input id="inline_vendor_email" v-model="form.email" placeholder="vendor@example.com" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="inline_vendor_phone">Phone</Label>
                            <Input id="inline_vendor_phone" v-model="form.phone" placeholder="0200 000 000" />
                            <InputError :message="form.errors.phone" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="inline_vendor_service_type">Service type</Label>
                        <Input id="inline_vendor_service_type" v-model="form.service_type" placeholder="Electrical, HVAC, Plumbing" />
                        <InputError :message="form.errors.service_type" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="ghost" @click="closeDialog">Cancel</Button>
                    <Button type="button" :disabled="form.processing" @click="submit">
                        Create vendor
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
