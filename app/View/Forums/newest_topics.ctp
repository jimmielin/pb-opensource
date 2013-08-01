<table class="table table-striped table-bordered">
<?php
	foreach($data["Topic"] as $key => $value) {
		?>
		<tr>
			<td>
				<h3><a href="<?php echo $this->Iris->forumURL("topics/view/" . $value["id"]); ?>"><?php echo $value["title"]; ?></a></h3>
				<h6><?php echo __("Posted on ") . $this->Time->format("Y-m-d H:i:s", $value["created"]) . " - by " . $value["User"]["username"]; ?></h6>
				<?php echo $this->IrisIO->parse($value["content"]); ?>
			</td>
		</tr>
		<?php
	}
?>
</table>