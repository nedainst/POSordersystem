cmake_minimum_required(VERSION 3.16)
project(POSSystem VERSION 1.0.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)
set(CMAKE_AUTOUIC ON)

find_package(Qt6 REQUIRED COMPONENTS Widgets Sql)

set(SOURCES
    src/main.cpp
    src/database.cpp
    src/logindialog.cpp
    src/mainwindow.cpp
    src/pages/dashboardpage.cpp
    src/pages/pospage.cpp
    src/pages/productspage.cpp
    src/pages/inventorypage.cpp
    src/pages/historypage.cpp
    src/pages/reportspage.cpp
    src/pages/supplierspage.cpp
    src/pages/expensespage.cpp
    src/pages/userspage.cpp
    src/pages/settingspage.cpp
)

set(HEADERS
    src/styles.h
    src/database.h
    src/logindialog.h
    src/mainwindow.h
    src/pages/dashboardpage.h
    src/pages/pospage.h
    src/pages/productspage.h
    src/pages/inventorypage.h
    src/pages/historypage.h
    src/pages/reportspage.h
    src/pages/supplierspage.h
    src/pages/expensespage.h
    src/pages/userspage.h
    src/pages/settingspage.h
)

add_executable(${PROJECT_NAME} WIN32 ${SOURCES} ${HEADERS})
target_include_directories(${PROJECT_NAME} PRIVATE src src/pages)
target_link_libraries(${PROJECT_NAME} PRIVATE Qt6::Widgets Qt6::Sql)

# Copy Qt DLLs on Windows
if(WIN32)
    add_custom_command(TARGET ${PROJECT_NAME} POST_BUILD
        COMMAND ${CMAKE_COMMAND} -E copy_if_different
            $<TARGET_FILE:Qt6::Core>
            $<TARGET_FILE:Qt6::Gui>
            $<TARGET_FILE:Qt6::Widgets>
            $<TARGET_FILE:Qt6::Sql>
            $<TARGET_FILE_DIR:${PROJECT_NAME}>
    )
endif()
