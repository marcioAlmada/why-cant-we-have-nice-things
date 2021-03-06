<?php
namespace History\Services\RequestsGatherer\Synchronizers;

use History\Entities\Models\User;

class UserSynchronizer extends AbstractSynchronizer
{
    /**
     * @return User
     */
    public function synchronize()
    {
        $email    = $this->get('email');
        $username = $this->get('username');
        $fullName = $this->get('full_name');

        $components = [
            ['name', $username],
            ['email', $email],
            ['email', preg_replace('/@(.+)/', '@php.net', $email)],
            ['full_name', $fullName],
        ];

        // If we have no username but have an email
        // try to infere username from it
        if (!$username && $email) {
            $username = explode('@', $email)[0];
            $components[] = ['name', $username];
        }

        // Try to retrieve user if he's already an author
        $user = new User();
        foreach ($components as list($key, $value)) {
            $user = $user = User::firstOrNew([$key => $value]);
            if ($value && $user->exists) {
                break;
            }
        }

        // Fill-in informations
        $user                = $user->id ? $user : new User();
        $user->name          = $username;
        $user->full_name     = $fullName;
        $user->email         = $email;
        $user->contributions = $this->get('contributions') ?: [];

        return $user;
    }
}
