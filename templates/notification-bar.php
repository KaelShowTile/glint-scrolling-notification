<?php
$notification_manager = new Notification_Manager();
$notifications = $notification_manager->get_active_notifications();
$swiper_integration = new Swiper_Integration();
?>

<div class="glint-scrolling-notification">
    <?php echo $swiper_integration->get_swiper_html($notifications); ?>
</div>