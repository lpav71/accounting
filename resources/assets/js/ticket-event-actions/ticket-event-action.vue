<template>
    <div>
        <div class="row">
            <div class="col-6">
                <h3>Список добавления в чат</h3>
            </div>
            <div class="col-6">
                <h3>Все пользователи</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <draggable class="list-group" :list="list1" group="people">
                    <div
                            class="list-group-item"
                            v-for="(element, index) in list1"
                            :key="element.id"
                    >
                        {{ element.id }} {{ element.name }}
                    </div>
                </draggable>
            </div>

            <div class="col-6">
                <draggable class="list-group" :list="list2" group="people">
                    <div
                            class="list-group-item"
                            v-for="(element, index) in list2"
                            :key="element.id"
                    >
                        {{ element.id }} {{ element.name }}
                    </div>
                </draggable>
            </div>
        </div>
    </div>
</template>
<script>
    import draggable from "vuedraggable";

    export default {
        name: "two-lists",
        display: "Two Lists",
        order: 1,
        components: {
            draggable
        },
        data() {
            return {
                list1: [],
                list2: []
            };
        },
        methods: {},
        created() {
            this.list1 = JSON.parse(document.body.querySelector('#ticket-event-action-id').getAttribute('data-users-attached'));
            this.list2 = JSON.parse(document.body.querySelector('#ticket-event-action-id').getAttribute('data-users'));
        },
        watch: {
            list1: function () {
                document.body.querySelector('#ticket-users-input').setAttribute('value', JSON.stringify(this.list1));
            }
        }
    };
</script>
<style>
    .list-group {
        min-height: 100%;
    }
</style>