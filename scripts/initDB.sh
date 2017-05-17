userData="INSERT INTO users (username, email, hashed_password) VALUES ('Admin', 'tfruehe@gmail.com', 'password');"
permissionGroupData="INSERT INTO permission_groups (name, permissions) VALUES ('Administrator', '{\"admin\":1}');"
loadData="${permissionGroupData} ${userData}"
