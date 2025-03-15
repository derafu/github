<?php

declare(strict_types=1);

/**
 * Derafu: GitHub - Webhook handling and other sysadmin tasks.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\GitHub\Webhook\EventHandler;

use Derafu\GitHub\Webhook\Notification;

/**
 * Class for handling GitHub workflow run events.
 *
 * This class provides functionality to handle GitHub workflow run events and
 * deploy sites based on the workflow run status and conclusion.
 */
final class WorkflowRunHandler
{
    /**
     * Deploy a site based on the workflow run status and conclusion.
     *
     * @param Notification $notification The notification object containing the
     * workflow run data.
     * @param string $deployer The path to the deployer executable.
     * @param array $sites The sites configuration.
     * @return ?string The output of the deployment command or null if no
     * deployment is executed.
     */
    public static function deploy(
        Notification $notification,
        string $deployer,
        array $sites
    ): ?string {
        $payload = $notification->getPayload();

        $branch = $payload->workflow_run->head_branch;
        $workflow = $payload->workflow->name;
        $event = $payload->workflow_run->event;
        $status = $payload->workflow_run->status;
        $conclusion = $payload->workflow_run->conclusion;

        $httpUri = sprintf(
            'https://github.com/%s.git',
            $payload->repository->full_name
        );
        $gitUri = sprintf(
            'git@github.com:%s.git',
            $payload->repository->full_name
        );

        foreach ($sites as $site => $config) {
            if (is_string($config)) {
                $config = ['repository' => $config];
            }

            $siteRepository = $config['repository'];
            $siteBranch = $config['branch'] ?? 'main';
            $siteWorkflow = $config['workflow'] ?? 'CI';

            if (
                ($httpUri === $siteRepository || $gitUri === $siteRepository)
                && $branch === $siteBranch
                && $workflow === $siteWorkflow
                && $event === 'push'
                && $status === 'completed'
                && $conclusion === 'success'
            ) {
                $command = sprintf(
                    "%s derafu:deploy:single --site=\"%s\"",
                    escapeshellarg($deployer),
                    escapeshellarg($site)
                );
                return shell_exec($command);
            }
        }

        return null;
    }
}
