AUTH — sin protección de rol (solo JWT o público)                                                                                           
POST  /auth/login            → público                                                                                                      
POST  /auth/register         → solo ADMIN y auxiliar                                                                                                     
POST  /auth/change-password  → solo JWT (cualquier rol)

DOCS                                                                                                                                        
GET   /docs/openapi.json     → público
                                                                                                                                              
---                                                       
LIBROS
GET    /libros                → admin, auxiliar, lector                                                                                  
GET    /libros/marc21         → admin, auxiliar        
GET    /libros/{id}           → admin, auxiliar, lector                                                                                  
GET    /libros/{id}/marc21    → admin, auxiliar        
POST   /libros                → admin                                                                                          
PATCH  /libros/{id}           → admin
DELETE /libros/{id}           → admin

ARTÍCULOS                                                                                                                                   
GET    /articulos                             → admin, auxiliar, lector
GET    /articulos/{id}                        → admin, auxiliar, lector                                                       
PATCH  /articulos/{id}                        → admin                                                                         
DELETE /articulos/{id}                        → admin
GET    /articulos/{idArticulo}/temas          → admin, lector                                                          
POST   /articulos/{idArticulo}/temas/{idTema} → admin                                                                          
DELETE /articulos/{idArticulo}/temas/{idTema} → admin

EJEMPLARES                                                
GET    /ejemplares                                      → admin, auxiliar, lector                                                        
GET    /ejemplares/{id}                                 → admin, auxiliar, lector
POST   /ejemplares                                      → admin                                                            
PUT    /ejemplares/{id}                                 → admin                                                               
DELETE /ejemplares/{id}                                 → admin             
PATCH  /ejemplares/{id}/habilitar                       → admin                                                                
PATCH  /ejemplares/{id}/deshabilitar                    → admin
GET    /articulos/{articuloId}/ejemplares               → admin, auxiliar, lector                                                        
GET    /articulos/{articuloId}/ejemplares/habilitados   → admin, auxiliar, lector

TEMAS                                                     
GET    /temas       → admin, auxiliar, lector                                                                                            
GET    /temas/{id}  → admin, auxiliar, lector          
POST   /temas       → admin                                                                                                    
PUT    /temas/{id}  → admin
DELETE /temas/{id}  → admin
                                                                                                                                              
---
CARRERAS                                                                                                                                    
GET    /carreras       → admin, auxiliar, lector       
GET    /carreras/{id}  → admin, auxiliar, lector
POST   /carreras       → admin                                                                                                              
PATCH  /carreras/{id}  → admin
DELETE /carreras/{id}  → admin

LECTORES                                                                                                                                    
GET    /lectores/mi-perfil                              → lector
POST   /lectores/{lectorId}/carreras/{carreraId}        → admin                                                                             
DELETE /lectores/{lectorId}/carreras/{carreraId}        → admin
                                                                                                                                              
---                                                                                                                                         
PRÉSTAMOS                                                                                                                                   
GET    /prestamos                      → admin, auxiliar                                                                                    
GET    /prestamos/{id}                 → admin, auxiliar    
GET    /lectores/me/prestamos          → lector                                                                                      
GET    /lector/{lectorId}/prestamos    → admin        
POST   /prestamos                      → admin, auxiliar                                                                                            
PATCH  /prestamos/{id}/devolver        → admin                                                                                            
PATCH  /prestamos/{id}/renovar         → lector

RESERVAS                                                                                                                                    
GET    /reservas                  → admin, auxiliar
GET    /reservas/{id}             → admin, auxiliar                                                                                 
GET    /lectores/me/reservas      → lector         
POST   /reservas                  → lector
PATCH  /reservas/{id}/cancelar    → lector

TIPOS DE PRÉSTAMO                                                                                                                           
GET    /tipos-prestamos                      → admin, auxiliar                                                                 
GET    /tipos-prestamos/{id}                 → admin, auxiliar
POST   /tipos-prestamos                      → admin                                                                                        
PATCH  /tipos-prestamos/{id}                 → admin                                                                                        
PATCH  /tipos-prestamos/{id}/habilitar       → admin
PATCH  /tipos-prestamos/{id}/deshabilitar    → admin 