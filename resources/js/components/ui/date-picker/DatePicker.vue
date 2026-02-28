<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { DateFormatter, type DateValue, getLocalTimeZone, parseDate } from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    modelValue?: string | null;
    defaultValue?: string | null;
    placeholder?: string;
    name?: string;
    id?: string;
    required?: boolean;
    disabled?: boolean;
    class?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
});

const getInitialValue = (val: string | null): DateValue | undefined => {
    if (!val) return undefined;
    try {
        return parseDate(val);
    } catch (e) {
        return undefined;
    }
};

const internalValue = ref<DateValue | undefined>(
    getInitialValue(props.modelValue ?? props.defaultValue ?? null),
);

watch(() => props.modelValue, (newVal) => {
    if (newVal === undefined) {
        return;
    }

    if (newVal) {
        try {
            const parsed = parseDate(newVal);
            if (internalValue.value?.toString() !== parsed.toString()) {
                internalValue.value = parsed;
            }
        } catch {
            internalValue.value = undefined;
        }
    } else {
        internalValue.value = undefined;
    }
});

watch(internalValue, (newVal) => {
    const stringVal = newVal ? newVal.toString() : null;
    if (props.modelValue !== stringVal && props.modelValue !== undefined) {
        emit('update:modelValue', stringVal);
    }
});
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                :id="props.id"
                variant="outline"
                :disabled="props.disabled"
                :class="cn(
                    'w-full justify-start text-left font-normal h-9 px-3',
                    !internalValue && 'text-muted-foreground',
                    props.class,
                )"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ internalValue ? df.format(internalValue.toDate(getLocalTimeZone())) : (props.placeholder || 'Pick a date') }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar v-model="internalValue" initial-focus />
        </PopoverContent>
    </Popover>
    <input
        v-if="props.name"
        :name="props.name"
        type="hidden"
        :required="props.required"
        :value="internalValue ? internalValue.toString() : ''"
    />
</template>
