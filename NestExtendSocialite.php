<?php

namespace SocialiteProviders\Nest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NestExtendSocialite
{
  /**
   * Register the provider.
   *
   * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
   */
  public function handle(SocialiteWasCalled $socialiteWasCalled)
  {
    $socialiteWasCalled->extendSocialite(
      'nest', __NAMESPACE__.'\Provider'
    );
  }
}
