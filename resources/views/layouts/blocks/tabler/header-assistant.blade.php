<div class="nav-item d-none d-flex" id="modules-assistant">
    <a v-on:click.prevent="modulesAssistant" class="btn btn-sm btn-outline-primary" :disabled="loadingAssistant">
    	<span class="d-none d-md-inline">Help</span>
        <span class="d-inline d-md-none"> &nbsp; <i class="dropdown-icon fe fe-help-circle"></i></span>
    </a>
    @include('modules-assistant::modals.assistant')
</div>


<!-- <div class="dropdown d-none d-md-flex" id="notification-container">
    <a class="nav-link icon" data-toggle="dropdown">
        <i class="fe fe-bell"></i>
        <span class="" v-bind:class="{'nav-unread': notificationMessages.length > 0}"></span>
    </a>
    <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
        <a v-if="notificationMessages.length === 0" href="#" class="dropdown-item d-flex">
            <div>
                Nothing pending...
            </div>
        </a>
        <tabler-notification-item v-for="(notification, index) in notificationMessages" :key="'n-' + index"
                                  :notification="notification" :index="index"></tabler-notification-item>
        <div v-if="notificationMessages.length > 0" class="dropdown-divider"></div>
        <a v-if="notificationMessages.length > 0" href="#" class="dropdown-item text-center text-muted-dark">Mark all as read</a>
    </div>
</div> -->
