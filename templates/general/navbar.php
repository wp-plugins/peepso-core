<?php peepso('load-template', 'general/js-unavailable'); ?>

<?php if (is_user_logged_in()) { ?>
	<nav class="ps-toolbar">
		<ul class="js-toolbar">
			<li>
				<a href="javascript:" class="ps-toolbar-toggle" data-toggle="collapse" data-target="#ps-main-nav">
					<i class="ps-icon-menu"></i>
				</a>
			</li>
			<?php peepso('general', 'navbar-mobile'); ?>
		</ul>
	</nav>
	<nav id="ps-main-nav" class="ps-toolbar-menu">
		<ul>
			<?php peepso('general', 'navbar-sidebar-mobile'); ?>
		</ul>
	</nav>
	<nav class="ps-toolbar-desktop">
		<ul class="js-toolbar">
			<?php peepso('general', 'navbar'); ?>
		</ul>
	</nav>
<?php } ?>
