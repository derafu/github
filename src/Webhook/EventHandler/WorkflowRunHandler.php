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
use Derafu\GitHub\Webhook\Response;

/**
 * Class for handling GitHub workflow run events.
 *
 * This class provides functionality to handle GitHub workflow run events and
 * deploy sites based on the workflow run status and conclusion.
 *
 * The deployer is done using derafu/deployer, so the deployer path must be
 * provided.
 */
final class WorkflowRunHandler
{
    /**
     * Deploy a site based on the workflow run status and conclusion.
     *
     * @param Notification $notification The notification object containing the
     * workflow run data.
     * @param string $deployerDir The path to the deployer directory.
     * @return ?Response The response of the deployment command or null if no
     * deployment is executed.
     */
    public static function deploy(
        Notification $notification,
        ?string $deployerDir = null
    ): ?Response {
        // Get the workflow run data.
        $workflowRun = $notification->getPayload()->workflow_run;
        $httpsUri = sprintf(
            'https://github.com/%s.git',
            $workflowRun->repository->full_name
        );
        $gitUri = sprintf(
            'git@github.com:%s.git',
            $workflowRun->repository->full_name
        );
        $workflow = $workflowRun->name;
        $branch = $workflowRun->head_branch;
        $event = $workflowRun->event;
        $status = $workflowRun->status;
        $conclusion = $workflowRun->conclusion;
        $username = $workflowRun->actor->login;

        // Get the deployer configuration.
        $deployerDir = $deployerDir
            ?? (getenv('DEPLOYER_DIR') ?: realpath('/home/admin/deployer'))
        ;
        $deployerBin = $deployerDir . '/vendor/bin/dep';
        $deployerFile = $deployerDir . '/deploy.php';
        $sites = require $deployerDir . '/sites.php';
        $logFile = '/var/log/deployer.log';

        // Search the site that matches the workflow run repository, and others
        // criteria, and deploy it.
        foreach ($sites as $site => $config) {
            if (is_string($config)) {
                $config = ['repository' => $config];
            }

            $siteRepository = $config['repository'];
            $siteWorkflow = $config['workflow'] ?? 'CI';
            $siteBranch = $config['branch'] ?? 'main';
            $siteUsername = $config['username'] ?? null;

            if (
                ($httpsUri === $siteRepository || $gitUri === $siteRepository)
                && $branch === $siteBranch
                && $workflow === $siteWorkflow
                && $event === 'push'
                && $status === 'completed'
                && $conclusion === 'success'
                && (
                    $siteUsername === null
                    || ($siteUsername !== null && $username === $siteUsername)
                )
            ) {
                $command = sprintf(
                    '%s -f %s derafu:deploy:single --site=%s',
                    escapeshellarg($deployerBin),
                    escapeshellarg($deployerFile),
                    escapeshellarg($site)
                );
                $command = sprintf(
                    'echo "%s >> %s 2>&1" | at now',
                    $command,
                    escapeshellarg($logFile)
                );

                $output = [];
                $result_code = 0;
                exec($command, $output, $result_code);

                $notification->setResponse(new Response([
                    'code' => $result_code,
                    'data' => [
                        'command' => $command,
                        'output' => implode("\n", $output),
                        'result_code' => $result_code,
                    ],
                ]));

                return $notification->getResponse();
            }
        }

        return null;
    }
}
