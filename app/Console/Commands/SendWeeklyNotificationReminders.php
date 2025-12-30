<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyReminderEmail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;

class SendWeeklyNotificationReminders extends Command
{
    /**
     * cara kerja:
     * 1. command ini dijalankan setiap minggu via scheduler (cronjob)
     * 2. command bakal jalanin worker (SendWeeklyReminderEmail)
     * 3. worker bakal ngirim email (WeeklyNotificationRemainderNotification) yg isi kontennya ada di weekly-notification-reminder.blade.php
     *
     * @var string
     */
    protected $signature = 'reminders:weekly-notifications
                            {--limit= : The number of notifications to send each week}
                            {--dry-run : Simulate the command without sending notifications}'; // yang ada -- berarti parameter optional dan setelah : merupakan deskripsinya yang muncul saat php artisan list atau php artisan help reminders:weekly-notifications
    //    php artisan reminders:weekly-notifications --limit=50 --dry-run

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly notification reminders to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to send weekly notification reminders.');
        $startTime = microtime(true);
        $query = User::whereNotNull('email_verified_at');

        $limit = (int) $this->option('limit');
        if ($limit) {
            $query->limit($limit);
            $this->info('Limiting to ' . $limit . ' users this week.');
        }

        $total = $query->count();
        if ($total == 0) {
            $this->info('No users to send notifications to.');

            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(500, function ($users) use ($bar) {
            foreach ($users as $user) {
                $unreadCount = Notification::forUser($user->id)->unread()->count();
                if ($unreadCount > 0) {
                    if ($this->option('dry-run')) {
                        $this->info("Dry run: Would send reminder to user ID {$user->id} with {$unreadCount} unread notifications.");
                    } else {
                        SendWeeklyReminderEmail::dispatch($user->id, $unreadCount);
                    }
                }
                $bar->advance();
            }
            usleep(100000); // biar kg meleduk dbnya
        });

        $bar->finish();
        $executionTime = round(microtime(true) - $startTime, 2);
        $this->info("\nFinished sending weekly notification reminders in {$executionTime} seconds.");

        return 0;
    }
}
