CHANGELOG
===================

This changelog references the relevant changes that have been made to LemonRestBundle starting with version 0.7.

To get the diff for a specific change, go to https://github.com/stanlemon/rest-bundle/commit/XXX where XXX is the change hash
To get the diff between two versions, go to https://github.com/stanlemon/rest-bundle/compare/0.7.0...master

* 0.7.0 (2015-02-28)
 * BC Default to plural resource names, this means if you didn't explicit name your object your endpoints will change. For example /api/post -> /api/posts, you can override this behavior by manually configuring the name on the object.
 * BC Added a first class definition object, which is used by the object registry. The object registry methods changed during this refactor. This definition class will allow for better storage of configuration than the simple arrays that were originally being passed around.
 * Added the option to map a directory of entities. This allows for you to use LemonRestBundle with a non-automapped directory of Doctrine entities.
 * Added the option to configure method types for an object, in other words you can now enable 'post' for an object and disable 'delete'. There are attributes on the annotation to support this, and it can be handled manually when working directly with the object registry.
 * Added support for posting an id to a relation to load the mapped relation, in other words a comment can be posted with a numerical value for property post and this will become the appropriate Post instance.
