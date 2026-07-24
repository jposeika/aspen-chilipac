{strip}
	{if !empty($loggedIn)}
		<div id="profile-links" class="sidebar-links row">
			<div class="panel-group accordion" id="profile-link-accordion">
				<div class="panel active">
					<a data-toggle="collapse" href="#profilePanel" aria-label="{translate text='Profile Menu' inAttribute=true isPublicFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="My Profile" isPublicFacing=true}
							</div>
						</div>
					</a>
					<div id="profilePanel" class="panel-collapse collapse in">
						<div class="panel-body">
							<div class="myAccountLink{if $activeProfilePage == 'home'} active{/if}">
								<a href="/Profile/Home">{translate text="Overview" isPublicFacing=true}</a>
							</div>
							<div class="myAccountLink{if $activeProfilePage == 'booklists'} active{/if}">
								<a href="/Profile/Booklists">{translate text="Booklists" isPublicFacing=true}</a>
							</div>
							<div class="myAccountLink{if $activeProfilePage == 'bookshelf'} active{/if}">
								<a href="/Profile/Bookshelf">{translate text="Bookshelf" isPublicFacing=true}</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
{/strip}
