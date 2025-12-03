pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        // volitelné – pokud máš v agentovi php-cli
        // stage('PHP lint') {
        //     steps {
        //         sh '''
        //             find dashboard/web -name "*.php" -print0 | xargs -0 -n1 php -l
        //         '''
        //     }
        // }

        stage('Deploy to setonuk') {
            steps {
                sh '''
                    ssh quantum@dashboard.api.ventureout.cz '/opt/quantum/deploy.sh'
                '''
            }
        }
    }
}

