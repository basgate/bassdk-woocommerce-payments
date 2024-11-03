//Description on url
// https://www.ahmetkucukoglu.com/en/how-to-publish-asp-net-core-application-by-using-jenkins

String githubUrl = "https://github.com/basgate/bassdk-woocommerce-payments.git"
String projectName = "bassdk-woocommerce-payments"
String userName = "basgate-_979mf9lyuyh"
String iisApplicationPath = "C:\\inetpub\\vhosts\\basgate-sandbox.com\\wp-plugin.basgate-sandbox.com\\wp-content\\plugins\\bassdk-woocommerce-payments\\"


node () {
    stage('Clean Workspace'){
      // cleanWs()
      properties([
        disableConcurrentBuilds(abortPrevious: true)])
    }
    stage('Checkout') {
        checkout([
            $class: 'GitSCM',
            branches: [[name: 'production']],
            doGenerateSubmoduleConfigurations: false,
            extensions: [],
            submoduleCfg: [],
            userRemoteConfigs: [[credentialsId: 'Bas-Token',url: """ "${githubUrl}" """]]])
    }
    stage('Deploy'){
      dir("""${WORKSPACE}""") {
            bat """
            xcopy * "${iisApplicationPath}" /Y /E
            """
      }
    }
    stage('Delete .hidden files'){
      dir("""${WORKSPACE}""") {
            bat """
            del "${iisApplicationPath}"\\.gitignore
            """
      }
    }
    // stage('Folder Security') {
    //         bat """
    //         icacls "${iisApplicationPath}" /grant ${userName}:(OI)(CI)F /T
    //         """
    // }
}

