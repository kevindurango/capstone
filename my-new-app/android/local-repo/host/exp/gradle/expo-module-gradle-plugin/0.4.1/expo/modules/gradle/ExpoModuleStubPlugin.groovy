package expo.modules.gradle

import org.gradle.api.Plugin
import org.gradle.api.Project

class ExpoModuleStubPlugin implements Plugin<Project> {
    void apply(Project project) {
        // Stub plugin - does nothing
        project.logger.info("Applied expo-module-gradle-plugin stub")
    }
}
