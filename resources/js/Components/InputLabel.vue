<script setup>
import { computed } from "vue";

const props = defineProps({
    value: String,
    required: {
        type: Boolean,
        default: false,
    },
});

const normalizedValue = computed(() => (props.value ?? "").replace(/\s*\*$/, ""));
const showRequired = computed(() => props.required || /\*$/.test(props.value ?? ""));
</script>

<template>
    <label class="form-label" v-if="value">
        {{ normalizedValue }} <span v-if="showRequired" class="text-danger">*</span>
    </label>
</template>
