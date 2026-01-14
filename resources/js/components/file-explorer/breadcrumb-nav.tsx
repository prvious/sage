import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Fragment } from 'react';

interface BreadcrumbData {
    name: string;
    path: string;
}

interface BreadcrumbNavProps {
    breadcrumbs: BreadcrumbData[];
    onBreadcrumbClick: (path: string) => void;
}

export function BreadcrumbNav({ breadcrumbs, onBreadcrumbClick }: BreadcrumbNavProps) {
    if (breadcrumbs.length === 0) {
        return null;
    }

    return (
        <Breadcrumb>
            <BreadcrumbList>
                {breadcrumbs.map((crumb, index) => (
                    <Fragment key={crumb.path}>
                        <BreadcrumbItem>
                            <BreadcrumbLink onClick={() => onBreadcrumbClick(crumb.path)} className='cursor-pointer hover:text-foreground'>
                                {crumb.name}
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        {index < breadcrumbs.length - 1 && <BreadcrumbSeparator />}
                    </Fragment>
                ))}
            </BreadcrumbList>
        </Breadcrumb>
    );
}
