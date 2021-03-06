<div id="content" class="fit">
	
	<div id="<?= (! isset($id)) ? 'section' : $id ?>">
	
		<?php $this->load->view($this->template.'/layouts/top_nav');?>
		
		<div id="browser" class="grid">
			<?php foreach ($items->content as $key => $item): ?>
				
				<div class="item rounded-st <?php echo $item->type.'_'.$item->ratingKey?>">
					
					<a href="<?=link_item($link, $item, $this->uri->segment(4))?>">
						
						<div class="img">
							<div class="rounded">
								<?= $this->transcode->img($item, array('width' => 110, 'height' => 150)) ?>
							</div>
						</div>
						<div class="h3">
							<h3 class="h4"><?=character_limiter(title($item), 20)?></h3>
							<span>
								<?php $alt = (isset($item->year)) ? $item->year : '' ?>
								<?=(isset($item->leafCount)) ? $item->leafCount. " ".lang($item->type.'.childs') : $alt?>
							</span>
						</div>
					</a>
				</div>
			<?php endforeach ?>
		</div>
	</div>
</div>