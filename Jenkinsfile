pipeline {
    agent any

    stages {
        stage('Static Analysis') {
            steps {
                build job: 'GVV_Static_Analysis'
            }
        }

        stage('Unit Tests') {
            steps {
                build job: 'GVV-PHPUnit-Tests'
            }
        }

        stage ('Coverage') {
            steps {
                build job: 'GVV-PHPUnit-Coverage'
            }
        }

        stage ('Update target') {
            steps {
                build job: 'GVV_Update_Target_Server'
            }
        }
        
        stage('Playwright Tests') {
            steps {
                build job: 'GVV-Playwright'
            }
        }
    }
}
