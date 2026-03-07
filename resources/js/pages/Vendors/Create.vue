<script setup lang="ts">
import VendorController from '@/actions/App/Http/Controllers/VendorController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as vendorsIndex } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vendors',
        href: vendorsIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const selectedStatus = ref('active');

</script>

<template>
    <Head title="Create vendor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create vendor" subtitle="Add a new vendor profile." />

            <Form
                v-bind="VendorController.store.form()"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="status" :value="selectedStatus" />
                <div class="grid gap-2">
                    <Label for="name">Vendor name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="Acme Services"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        placeholder="vendor@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        name="phone"
                        placeholder="(555) 000-0000"
                    />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="service_type">Service type</Label>
                    <Input
                        id="service_type"
                        name="service_type"
                        placeholder="HVAC, Electrical, Plumbing"
                    />
                    <InputError :message="errors.service_type" />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <Select v-model="selectedStatus" name="status">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">Create vendor</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="vendorsIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
