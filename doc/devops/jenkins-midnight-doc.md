# Jenkins Conditional Midnight Build Pipeline

This document describes a Jenkins pipeline that executes builds at midnight, but only if there have been no builds since the last commit. This is useful for scenarios where you want to ensure nightly builds only run when there are unbuilt changes.

## Pipeline Overview

The pipeline implements the following logic:
1. Scheduled to run at midnight every day
2. Checks for new commits since the last build
3. Verifies no builds have occurred since the last commit
4. Executes the build only if both conditions are met

## Implementation

Here's the complete pipeline implementation:

```groovy
pipeline {
    agent any
    
    // Define parameters to store the last commit hash
    parameters {
        string(name: 'LAST_BUILT_COMMIT', defaultValue: '', description: 'Hash of the last built commit')
    }
    
    triggers {
        // Schedule to run at midnight
        cron('0 0 * * *')
    }
    
    stages {
        stage('Check Last Commit') {
            steps {
                script {
                    // Get the latest commit hash
                    def currentCommit = sh(script: 'git rev-parse HEAD', returnStdout: true).trim()
                    
                    // Get the timestamp of the latest commit
                    def lastCommitTime = sh(script: 'git log -1 --format=%ct', returnStdout: true).trim()
                    
                    // Get the timestamp of the last successful build
                    def lastBuildTime = currentBuild.previousSuccessfulBuild?.timestamp?.time ?: 0
                    
                    // Convert git timestamp to milliseconds for comparison
                    lastCommitTime = lastCommitTime.toLong() * 1000
                    
                    // Check if this commit has already been built
                    if (currentCommit == params.LAST_BUILT_COMMIT) {
                        echo "No new commits since last build. Skipping execution."
                        currentBuild.result = 'NOT_BUILT'
                        return
                    }
                    
                    // Check if there was any build after the last commit
                    if (lastBuildTime > lastCommitTime) {
                        echo "There was already a build after the last commit. Skipping execution."
                        currentBuild.result = 'NOT_BUILT'
                        return
                    }
                    
                    // If we reach here, we should proceed with the build
                    echo "New commits found and no builds since last commit. Proceeding with build."
                }
            }
        }
        
        stage('Main Build') {
            steps {
                script {
                    // Your main build steps go here
                    echo "Executing main build steps..."
                    
                    // Store the current commit hash for future reference
                    def currentCommit = sh(script: 'git rev-parse HEAD', returnStdout: true).trim()
                    sh "echo '${currentCommit}' > .last_built_commit"
                }
            }
        }
    }
    
    post {
        success {
            script {
                // Update the parameter with the latest built commit
                def builtCommit = sh(script: 'cat .last_built_commit', returnStdout: true).trim()
                properties([
                    parameters([
                        string(name: 'LAST_BUILT_COMMIT', defaultValue: builtCommit)
                    ])
                ])
            }
        }
    }
}
```

## Key Components Explained

### Schedule Configuration
The pipeline uses Jenkins' cron syntax to schedule execution at midnight:
```groovy
triggers {
    cron('0 0 * * *')
}
```

### State Management
The pipeline maintains state between builds using a Jenkins parameter:
```groovy
parameters {
    string(name: 'LAST_BUILT_COMMIT', defaultValue: '', description: 'Hash of the last built commit')
}
```

### Commit Check Logic
The pipeline performs two critical checks:

1. **New Commits Check**: Compares the current commit hash with the last built commit:
```groovy
if (currentCommit == params.LAST_BUILT_COMMIT) {
    echo "No new commits since last build. Skipping execution."
    currentBuild.result = 'NOT_BUILT'
    return
}
```

2. **Build Timing Check**: Ensures no builds have occurred since the last commit:
```groovy
if (lastBuildTime > lastCommitTime) {
    echo "There was already a build after the last commit. Skipping execution."
    currentBuild.result = 'NOT_BUILT'
    return
}
```

## Setup Instructions

1. In Jenkins, create a new Pipeline job
2. Copy the pipeline code into the Pipeline script section
3. Ensure Jenkins has access to your git repository
4. Replace the placeholder in the 'Main Build' stage with your actual build steps
5. Save the pipeline configuration

## Common Issues and Troubleshooting

1. **Git Access**: Ensure Jenkins has proper access to your git repository
2. **Time Zones**: The midnight schedule runs according to Jenkins server time
3. **Parameter Updates**: If the parameter isn't updating, check Jenkins job permissions

## Additional Notes

- The pipeline uses Git commands, so ensure Git is installed on your Jenkins agent
- The build history is preserved through Jenkins' built-in build record system
- The pipeline can be triggered manually, but will still respect the commit-check logic
- Failed builds don't update the `LAST_BUILT_COMMIT` parameter, ensuring they will be retried

## Customization

You can customize this pipeline by:
1. Modifying the cron schedule for different timing
2. Adding additional conditions for build execution
3. Expanding the main build stage with your specific build steps
4. Adding notification steps in the post section
5. Including additional error handling

## Security Considerations

1. Ensure proper access controls on Jenkins
2. Use credentials management for any sensitive repository access
3. Consider implementing approval steps for manual triggers
4. Review script security settings in Jenkins
